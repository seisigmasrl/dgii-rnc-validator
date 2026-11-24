<?php

use Seisigma\DgiiRncValidator\DgiiRncValidator;
use Seisigma\DgiiRncValidator\Exceptions\DgiiServiceException;
use Seisigma\DgiiRncValidator\helpers\Types;

function fixture(string $name): string
{
    return file_get_contents(__DIR__.'/Fixtures/'.$name);
}

/**
 * A fake DGII: answers the form request and the search request with the given responses,
 * and records every request it gets.
 */
function fakeDgii(array $form, array $search): object
{
    $recorder = new class
    {
        public array $requests = [];
    };

    DgiiRncValidator::useTransport(function (string $url, ?string $postData, array $headers) use ($form, $search, $recorder) {
        $recorder->requests[] = ['url' => $url, 'post' => $postData, 'headers' => $headers];

        return $postData === null ? $form : $search;
    });

    return $recorder;
}

function page(string $body, array $headers = ['HTTP/1.1 200 OK']): array
{
    return ['body' => $body, 'headers' => $headers, 'error' => null];
}

/**
 * A fake DGII that answers each search request with the next response in the list.
 */
function fakeDgiiSearches(array $searches): object
{
    $recorder = new class
    {
        public int $searches = 0;
    };

    DgiiRncValidator::useTransport(function (string $url, ?string $postData) use ($searches, $recorder) {
        if ($postData === null) {
            return page(fixture('form.html'));
        }

        return $searches[$recorder->searches++];
    });

    return $recorder;
}

function searchErrorPage(): array
{
    return page(str_replace(
        'El RNC/Cédula consultado no se encuentra inscrito como Contribuyente.',
        '24/11/2026 12:35 : Ocurri&#243; un error al intentar realizar la b&#250;squeda. Capture esta pantalla y rem&#237;tala a la Oficina Virtual.',
        fixture('not_found.html'),
    ));
}

beforeEach(fn () => DgiiRncValidator::retry(2, 0));

afterEach(function () {
    DgiiRncValidator::useTransport(null);
    DgiiRncValidator::retry();
});

test('check if the given string is a valid RNC', function () {
    expect(DgiiRncValidator::validateRNC('123'))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC('123456789'))->toBeTrue()
        ->and(DgiiRncValidator::validateRNC('1234567890'))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC('12345678901'))->toBeTrue();
});

test('check rncType return the type name', function () {
    expect(DgiiRncValidator::rncType('123456789'))->toBe(Types::RNC)
        ->and(DgiiRncValidator::rncType('12345678901'))->toBe(Types::CEDULA);
});

test('check if the given string without numbers is a valid RNC', function () {
    expect(DgiiRncValidator::validateRNC('abc cdd'))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC(' '))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC(''))->toBeFalse();
});

it('returns the taxpayer details when DGII finds the RNC', function () {
    fakeDgii(page(fixture('form.html')), page(fixture('found.html')));

    expect(DgiiRncValidator::check('132620951'))->toBe([
        'rnc' => '132620951',
        'name' => 'KOI CORPORATION BY SAIKOV SRL',
        'commercial_name' => 'KOI CORPORATION BY SAIKOV',
        'status' => 'ACTIVO',
    ]);
});

it('returns false when DGII says the RNC is not registered', function () {
    fakeDgii(page(fixture('form.html')), page(fixture('not_found.html')));

    expect(DgiiRncValidator::check('000000000'))->toBeFalse();
});

it('throws with the reason when DGII answers the search without data or a message', function () {
    fakeDgii(page(fixture('form.html')), page(fixture('form.html')));

    expect(fn () => DgiiRncValidator::check('132620951'))
        ->toThrow(DgiiServiceException::class, 'without taxpayer data');
});

it('sends the cookies of the form page with the search request', function () {
    $dgii = fakeDgii(
        page(fixture('form.html'), [
            'HTTP/1.1 200 OK',
            'Set-Cookie: NSC_LB=abc123;expires=Tue, 24-Nov-2026 16:27:58 GMT;path=/;secure;httponly',
            'Set-Cookie: ASP.NET_SessionId=xyz; path=/; HttpOnly',
        ]),
        page(fixture('found.html')),
    );

    DgiiRncValidator::check('132620951');

    expect($dgii->requests)->toHaveCount(2)
        ->and($dgii->requests[1]['headers'])->toContain('Cookie: NSC_LB=abc123; ASP.NET_SessionId=xyz')
        ->and($dgii->requests[0]['headers'])->not->toContain('Cookie: NSC_LB=abc123; ASP.NET_SessionId=xyz');
});

it('reads the status of the last response after a redirect', function () {
    fakeDgii(
        page(fixture('form.html'), ['HTTP/1.1 302 Found', 'Location: /x', 'HTTP/1.1 200 OK']),
        page(fixture('found.html')),
    );

    expect(DgiiRncValidator::check('132620951'))->toBeArray();
});

it('throws a server error with the status code on a 5xx answer', function () {
    fakeDgii(page('Service Unavailable', ['HTTP/1.1 503 Service Unavailable']), page(''));

    try {
        DgiiRncValidator::check('132620951');
        $this->fail('No exception was thrown.');
    } catch (DgiiServiceException $e) {
        expect($e->getMessage())->toContain('HTTP 503')
            ->and($e->context())->toMatchArray(['step' => 'form', 'http_status' => 503]);
    }
});

it('throws access denied on a 403 answer', function () {
    fakeDgii(page('Forbidden', ['HTTP/1.1 403 Forbidden']), page(''));

    expect(fn () => DgiiRncValidator::check('132620951'))
        ->toThrow(DgiiServiceException::class, 'Access denied');
});

it('throws an unexpected status error on a 4xx answer that is not 403', function () {
    fakeDgii(page(fixture('form.html')), page('Not Found', ['HTTP/1.1 404 Not Found']));

    try {
        DgiiRncValidator::check('132620951');
        $this->fail('No exception was thrown.');
    } catch (DgiiServiceException $e) {
        expect($e->getMessage())->toContain('HTTP 404')
            ->and($e->context())->toMatchArray(['step' => 'search', 'http_status' => 404]);
    }
});

it('throws a timeout when the request times out', function () {
    fakeDgii(['body' => false, 'headers' => [], 'error' => 'file_get_contents(): Read timed out'], page(''));

    expect(fn () => DgiiRncValidator::check('132620951'))
        ->toThrow(DgiiServiceException::class, 'timed out');
});

it('throws a connection error with the network reason', function () {
    fakeDgii(['body' => false, 'headers' => [], 'error' => 'php_network_getaddresses: getaddrinfo failed'], page(''));

    expect(fn () => DgiiRncValidator::check('132620951'))
        ->toThrow(DgiiServiceException::class, 'getaddrinfo failed');
});

it('asks DGII again when its search fails once', function () {
    $dgii = fakeDgiiSearches([searchErrorPage(), page(fixture('found.html'))]);

    expect(DgiiRncValidator::check('132620951'))->toMatchArray(['name' => 'KOI CORPORATION BY SAIKOV SRL'])
        ->and($dgii->searches)->toBe(2);
});

it('gives up after the last attempt and reports the DGII message and the attempts', function () {
    $dgii = fakeDgiiSearches([searchErrorPage(), searchErrorPage(), searchErrorPage()]);

    try {
        DgiiRncValidator::check('132620951');
        $this->fail('No exception was thrown.');
    } catch (DgiiServiceException $e) {
        expect($e->getMessage())->toContain('Ocurrió un error al intentar realizar la búsqueda')
            ->and($e->isRetryable())->toBeTrue()
            ->and($e->context())->toMatchArray(['step' => 'search', 'attempts' => 2])
            ->and($dgii->searches)->toBe(2);
    }
});

it('asks DGII again after a server error', function () {
    $dgii = fakeDgiiSearches([page('Down', ['HTTP/1.1 503 Service Unavailable']), page(fixture('found.html'))]);

    expect(DgiiRncValidator::check('132620951'))->toBeArray()
        ->and($dgii->searches)->toBe(2);
});

it('does not ask DGII again when access is denied', function () {
    $dgii = fakeDgiiSearches([page('Forbidden', ['HTTP/1.1 403 Forbidden']), page(fixture('found.html'))]);

    expect(fn () => DgiiRncValidator::check('132620951'))->toThrow(DgiiServiceException::class, 'Access denied')
        ->and($dgii->searches)->toBe(1);
});

it('does not ask DGII again when the RNC is not registered', function () {
    $dgii = fakeDgiiSearches([page(fixture('not_found.html')), page(fixture('found.html'))]);

    expect(DgiiRncValidator::check('000000000'))->toBeFalse()
        ->and($dgii->searches)->toBe(1);
});

it('asks DGII only once when retry is set to one attempt', function () {
    DgiiRncValidator::retry(1);
    $dgii = fakeDgiiSearches([searchErrorPage(), page(fixture('found.html'))]);

    expect(fn () => DgiiRncValidator::check('132620951'))->toThrow(DgiiServiceException::class)
        ->and($dgii->searches)->toBe(1);
});

it('can return the details of an RNC from the live DGII service', function () {
    $id = '132620951';
    expect(DgiiRncValidator::check($id))
        ->toMatchArray([
            'rnc' => $id,
            'name' => 'KOI CORPORATION BY SAIKOV SRL',
            'commercial_name' => 'KOI CORPORATION BY SAIKOV',
            'status' => 'ACTIVO',
        ])
        ->and(DgiiRncValidator::check('000000000'))
        ->toBeFalse();
})->group('live');
