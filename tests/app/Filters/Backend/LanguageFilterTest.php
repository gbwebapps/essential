<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

use App\Filters\Backend\LanguageFilter;

class LanguageFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();
        \CodeIgniter\Config\Factories::reset();

        parent::tearDown();
    }

    public function testBeforeUsesLanguageFromPost(): void
    {
        $request = $this->createMock(IncomingRequest::class);

        $request->method('getPost')->with('language')->willReturn('it');
        $request->expects($this->once())->method('setLocale')->with('it');

        $serviceRequest = $this->createMock(IncomingRequest::class);
        $serviceRequest->expects($this->once())->method('setLocale')->with('it');

        Services::injectMock('request', $serviceRequest);

        $appliedLocales = [];
        $language = $this->createMock(\CodeIgniter\Language\Language::class);
        $language->method('setLocale')
            ->willReturnCallback(function(?string $locale) use (&$appliedLocales, $language) {
                $appliedLocales[] = $locale;

                return $language;
            });

        Services::injectMock('language', $language);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoredLanguage'])
            ->getMock();

        $filter->expects($this->never())->method('getStoredLanguage');

        $filter->before($request);

        $this->assertSame('it', config('App')->defaultLocale);
        $this->assertContains('it', $appliedLocales);
    }

    public function testBeforeUsesStoredLanguageWhenPostLanguageIsMissing(): void
    {
        $request = $this->createMock(IncomingRequest::class);

        $request->method('getPost')->with('language')->willReturn(null);
        $request->expects($this->once())->method('setLocale')->with('en');

        $serviceRequest = $this->createMock(IncomingRequest::class);
        $serviceRequest->expects($this->once())->method('setLocale')->with('en');

        Services::injectMock('request', $serviceRequest);

        $language = $this->createMock(\CodeIgniter\Language\Language::class);
        $language->method('setLocale')->willReturnSelf();

        Services::injectMock('language', $language);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoredLanguage'])
            ->getMock();

        $filter->method('getStoredLanguage')->willReturn('en');

        $filter->before($request);

        $this->assertSame('en', config('App')->defaultLocale);
    }

    public function testBeforeRejectsUnsupportedPostedLanguage(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getPost')->with('language')->willReturn('unsupported-locale');
        $request->expects($this->once())->method('setLocale')->with('it');

        $serviceRequest = $this->createMock(IncomingRequest::class);
        $serviceRequest->expects($this->once())->method('setLocale')->with('it');
        Services::injectMock('request', $serviceRequest);

        $language = $this->createMock(\CodeIgniter\Language\Language::class);
        $language->method('setLocale')->willReturnSelf();
        Services::injectMock('language', $language);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->onlyMethods(['getStoredLanguage'])
            ->getMock();
        $filter->expects($this->once())->method('getStoredLanguage')->willReturn('it');

        $filter->before($request);

        $this->assertSame('it', config('App')->defaultLocale);
    }

    public function testBeforeRejectsNonStringPostedLanguage(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getPost')->with('language')->willReturn(['it']);
        $request->expects($this->once())->method('setLocale')->with('en');

        $serviceRequest = $this->createMock(IncomingRequest::class);
        $serviceRequest->expects($this->once())->method('setLocale')->with('en');
        Services::injectMock('request', $serviceRequest);

        $language = $this->createMock(\CodeIgniter\Language\Language::class);
        $language->method('setLocale')->willReturnSelf();
        Services::injectMock('language', $language);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->onlyMethods(['getStoredLanguage'])
            ->getMock();
        $filter->expects($this->once())->method('getStoredLanguage')->willReturn('en');

        $filter->before($request);

        $this->assertSame('en', config('App')->defaultLocale);
    }

    public function testBeforeFallsBackWhenStoredLanguageIsUnsupported(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getPost')->with('language')->willReturn(null);
        $request->expects($this->once())->method('setLocale')->with('en');

        $serviceRequest = $this->createMock(IncomingRequest::class);
        $serviceRequest->expects($this->once())->method('setLocale')->with('en');
        Services::injectMock('request', $serviceRequest);

        $language = $this->createMock(\CodeIgniter\Language\Language::class);
        $language->method('setLocale')->willReturnSelf();
        Services::injectMock('language', $language);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->onlyMethods(['getStoredLanguage'])
            ->getMock();
        $filter->method('getStoredLanguage')->willReturn('invalid');

        $filter->before($request);

        $this->assertSame('en', config('App')->defaultLocale);
    }

    public function testAfterReturnsNull(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $filter = new LanguageFilter();

        $result = $filter->after($request, $response);

        $this->assertNull($result);
    }

    public function testBeforeReturnsNullWhenRequestIsNotIncomingRequest(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $filter = new LanguageFilter();

        $result = $filter->before($request);

        $this->assertNull($result);
    }

    public function testGetStoredLanguageReturnsLanguage(): void
    {
        $config = new \Config\Backend\General();
        $config->language = 'en';
        \CodeIgniter\Config\Factories::injectMock('config', 'Backend\General', $config);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->onlyMethods(['getDatabase'])
            ->getMock();
        $filter->method('getDatabase')->willReturn($this->createLanguageDatabaseMock(null));

        $reflection = new \ReflectionMethod($filter, 'getStoredLanguage');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($filter);

        $this->assertSame('en', $result);
    }

    public function testGetStoredLanguageReadsLanguageFromConfig(): void
    {
        $config = new \Config\Backend\General();
        $config->language = 'it';

        \CodeIgniter\Config\Factories::injectMock(
            'config',
            'Backend\General',
            $config
        );

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->onlyMethods(['getDatabase'])
            ->getMock();
        $filter->method('getDatabase')->willReturn($this->createLanguageDatabaseMock(null));

        $reflection = new \ReflectionMethod($filter, 'getStoredLanguage');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($filter);

        $this->assertSame('it', $result);
    }

    public function testGetStoredLanguageUsesDatabaseValueBeforeConfig(): void
    {
        $config = new \Config\Backend\General();
        $config->language = 'en';
        \CodeIgniter\Config\Factories::injectMock('config', 'Backend\General', $config);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->onlyMethods(['getDatabase'])
            ->getMock();
        $filter->method('getDatabase')->willReturn(
            $this->createLanguageDatabaseMock((object) ['value' => 'it'])
        );

        $reflection = new \ReflectionMethod($filter, 'getStoredLanguage');
        $reflection->setAccessible(true);

        $this->assertSame('it', $reflection->invoke($filter));
    }

    public function testGetStoredLanguageHandlesDatabaseException(): void
    {
        $config = new \Config\Backend\General();
        $config->language = 'it';
        \CodeIgniter\Config\Factories::injectMock('config', 'Backend\General', $config);

        $filter = $this->getMockBuilder(LanguageFilter::class)
            ->onlyMethods(['getDatabase'])
            ->getMock();

        $filter->method('getDatabase')
            ->willThrowException(new \Exception('Database unavailable'));

        $reflection = new \ReflectionMethod($filter, 'getStoredLanguage');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($filter);

        $this->assertSame('it', $result);
    }

    private function createLanguageDatabaseMock(?object $row): \CodeIgniter\Database\BaseConnection
    {
        $result = $this->createMock(\CodeIgniter\Database\BaseResult::class);
        $result->expects($this->once())->method('getRow')->willReturn($row);

        $builder = $this->createMock(\CodeIgniter\Database\BaseBuilder::class);
        $builder->expects($this->once())
            ->method('whereIn')
            ->with('class', ['Backend\General', 'App\Config\Backend\General'])
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('where')
            ->with('key', 'language')
            ->willReturnSelf();
        $builder->expects($this->once())->method('get')->willReturn($result);

        $db = $this->createMock(\CodeIgniter\Database\BaseConnection::class);
        $db->expects($this->once())->method('table')->with('settings')->willReturn($builder);

        return $db;
    }
}
