<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PageCache\Test\Unit\Model\App\FrontController;

use Laminas\Http\Header\GenericHeader;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\PageCache\Kernel;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\State;
use Magento\Framework\Controller\ResultInterface;
use Magento\PageCache\Model\App\FrontController\BuiltinPlugin;
use Magento\PageCache\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuiltinPluginTest extends TestCase
{
    /**
     * @var BuiltinPlugin
     */
    protected $plugin;

    /**
     * @var Config|MockObject
     */
    protected $configMock;

    /**
     * @var Version|MockObject
     */
    protected $versionMock;

    /**
     * @var Kernel|MockObject
     */
    protected $kernelMock;

    /**
     * @var State|MockObject
     */
    protected $stateMock;

    /**
     * @var Http|MockObject
     */
    protected $responseMock;

    /**
     * @var FrontControllerInterface|MockObject
     */
    protected $frontControllerMock;

    /**
     * @var \Closure
     */
    protected $closure;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * SetUp
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->versionMock = $this->createMock(Version::class);
        $this->kernelMock = $this->createMock(Kernel::class);
        $this->stateMock = $this->createMock(State::class);
        $this->frontControllerMock = $this->createMock(FrontControllerInterface::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->responseMock = $this->createMock(Http::class);
        $response = $this->responseMock;
        $this->closure = function () use ($response) {
            return $response;
        };
        $this->plugin = new BuiltinPlugin(
            $this->configMock,
            $this->versionMock,
            $this->kernelMock,
            $this->stateMock
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAroundDispatchProcessIfCacheMissed($state)
    {
        $header = GenericHeader::fromString('Cache-Control: no-cache');
        $this->configMock
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Config::BUILT_IN));
        $this->configMock->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->versionMock
            ->expects($this->once())
            ->method('process');
        $this->kernelMock
            ->expects($this->once())
            ->method('load')
            ->will($this->returnValue(false));
        $this->stateMock->expects($this->any())
            ->method('getMode')
            ->will($this->returnValue($state));
        if ($state == State::MODE_DEVELOPER) {
            $this->responseMock->expects($this->at(1))
                ->method('setHeader')
                ->with('X-Magento-Cache-Control');
            $this->responseMock->expects($this->at(2))
                ->method('setHeader')
                ->with('X-Magento-Cache-Debug', 'MISS', true);
        } else {
            $this->responseMock->expects($this->never())
                ->method('setHeader');
        }
        $this->responseMock
            ->expects($this->once())
            ->method('getHeader')
            ->with('Cache-Control')
            ->will($this->returnValue($header));
        $this->kernelMock
            ->expects($this->once())
            ->method('process')
            ->with($this->responseMock);
        $this->assertSame(
            $this->responseMock,
            $this->plugin->aroundDispatch($this->frontControllerMock, $this->closure, $this->requestMock)
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAroundDispatchReturnsResultInterfaceProcessIfCacheMissed($state)
    {
        $this->configMock
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Config::BUILT_IN));
        $this->configMock->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->versionMock
            ->expects($this->once())
            ->method('process');
        $this->kernelMock
            ->expects($this->once())
            ->method('load')
            ->will($this->returnValue(false));
        $this->stateMock->expects($this->any())
            ->method('getMode')
            ->will($this->returnValue($state));

        $result = $this->createMock(ResultInterface::class);
        $result->expects($this->never())->method('setHeader');
        $closure =  function () use ($result) {
            return $result;
        };

        $this->assertSame(
            $result,
            $this->plugin->aroundDispatch($this->frontControllerMock, $closure, $this->requestMock)
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAroundDispatchReturnsCache($state)
    {
        $this->configMock
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Config::BUILT_IN));
        $this->configMock->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->versionMock
            ->expects($this->once())
            ->method('process');
        $this->kernelMock
            ->expects($this->once())
            ->method('load')
            ->will($this->returnValue($this->responseMock));

        $this->stateMock->expects($this->any())
            ->method('getMode')
            ->will($this->returnValue($state));
        if ($state == State::MODE_DEVELOPER) {
            $this->responseMock->expects($this->once())
                ->method('setHeader')
                ->with('X-Magento-Cache-Debug');
        } else {
            $this->responseMock->expects($this->never())
                ->method('setHeader');
        }
        $this->assertSame(
            $this->responseMock,
            $this->plugin->aroundDispatch($this->frontControllerMock, $this->closure, $this->requestMock)
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAroundDispatchDisabled($state)
    {
        $this->configMock
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue(null));
        $this->configMock->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->versionMock
            ->expects($this->once())
            ->method('process');
        $this->stateMock->expects($this->any())
            ->method('getMode')
            ->will($this->returnValue($state));
        $this->responseMock->expects($this->never())
            ->method('setHeader');
        $this->assertSame(
            $this->responseMock,
            $this->plugin->aroundDispatch($this->frontControllerMock, $this->closure, $this->requestMock)
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'developer_mode' => [State::MODE_DEVELOPER],
            'production' => [State::MODE_PRODUCTION],
        ];
    }
}
