<?php

require_once(__DIR__ . '/../../src/inc/Request.php');

/**
 * A class to test VersionedRouter
 *
 * @covers VersionedRouter
 */
class VersionedRouterTest extends PHPUnit_Framework_TestCase
{

    public function getRouteProvider()
    {
        return array(
            array( // #0
                'version' => '2.1',
                'rules' => array(
                    array(
                        'path' => '/events',
                        'controller' => 'EventController',
                        'action' => 'getAction'
                    )
                ),
                'url' => '/v2.1/events',
                'method' => Request::HTTP_GET,
                'expectedController' => 'EventController',
                'expectedAction' => 'getAction'
            ),
            array( // #1
                'version' => '2.1',
                'rules' => array(
                    array(
                        'path' => '/aevents',
                        'controller' => 'AEventController',
                        'action' => 'getSAction'
                    ),
                    array(
                        'path' => '/events',
                        'controller' => 'EventController',
                        'action' => 'getAction'
                    )
                ),
                'url' => '/v2.1/events',
                'method' => Request::HTTP_GET,
                'expectedController' => 'EventController',
                'expectedAction' => 'getAction'
            ),
            array( // #2
                'version' => '2.1',
                'rules' => array(
                    array(
                        'path' => '/events',
                        'controller' => 'EventController',
                        'action' => 'getAction',
                        'verbs' => array(Request::HTTP_POST)
                    ),
                    array(
                        'path' => '/events',
                        'controller' => 'EventController2',
                        'action' => 'getAction2',
                        'verbs' => array(Request::HTTP_GET, Request::HTTP_PUT)
                    )
                ),
                'url' => '/v2.1/events',
                'method' => Request::HTTP_GET,
                'expectedController' => 'EventController2',
                'expectedAction' => 'getAction2'
            ),
            array( // #3
                'version' => '2.1',
                'rules' => array(
                    array(
                        'path' => '/events/(?P<event_id>\d+)$',
                        'controller' => 'EventController',
                        'action' => 'getAction'
                    ),
                ),
                'url' => '/v2.1/events/10',
                'method' => Request::HTTP_GET,
                'expectedController' => 'EventController',
                'expectedAction' => 'getAction',
                'routeParams' => array('event_id' => 10)
            ),
            array( // #4
                'version' => '2.1',
                'rules' => array(
                    array(
                        'path' => '/aevents',
                        'controller' => 'AEventController',
                        'action' => 'getSAction'
                    ),
                    array(
                        'path' => '/events',
                        'controller' => 'EventController',
                        'action' => 'getAction',
                        'verbs' => array(Request::HTTP_GET)
                    )
                ),
                'url' => '/v2.1/events',
                'method' => Request::HTTP_POST,
                'expectedController' => 'N/A',
                'expectedAction' => 'N/A',
                'routeParams' => array(),
                'expectedExceptionCode' => 415
            ),
            array( // #5
                'version' => '2.1',
                'rules' => array(
                    array(
                        'path' => '/aevents',
                        'controller' => 'AEventController',
                        'action' => 'getSAction'
                    ),
                    array(
                        'path' => '/events',
                        'controller' => 'EventController',
                        'action' => 'getAction',
                        'verbs' => array(Request::HTTP_GET)
                    )
                ),
                'url' => '/v2.2/events',
                'method' => Request::HTTP_GET,
                'expectedController' => 'N/A',
                'expectedAction' => 'N/A',
                'routeParams' => array(),
                'expectedExceptionCode' => 404
            )
        );
    }

    /**
     * @dataProvider getRouteProvider
     * @covers VersionedRouter::getRoute
     *
     * @param float $version
     * @param array $rules
     * @param string $url
     * @param string $method
     * @param string $expectedController
     * @param string $expectedAction
     * @param array $routeParams
     * @param integer|false $expectedExceptionCode
     */
    public function testGetRoute($version, array $rules, $url, $method, $expectedController, $expectedAction, array $routeParams = array(), $expectedExceptionCode = false)
    {
        $router = $this->getMock('VersionedRouter', ['getLegacyRoute'], [$version, [], $rules]);
        $router->expects($this->any())
               ->method('getLegacyRoute')
               ->will($this->returnValue('fallen back'));
        $request = new Request([], ['REQUEST_URI' => $url, 'REQUEST_METHOD' => $method]);
        try {
            $route = $router->getRoute($request);
        } catch (Exception $ex) {
            if (!$expectedExceptionCode) {
                throw $ex;
            }
            $this->assertEquals($expectedExceptionCode, $ex->getCode());
            return;
        }
        $this->assertEquals($expectedController, $route->getController());
        $this->assertEquals($expectedAction, $route->getAction());
        $this->assertEquals($routeParams, $route->getParams());
    }

    /**
     * DataProvider for testRoute
     *
     * @return array
     */
    public function getLegacyRouteProvider()
    {
        return array(
            array( // #0
                'url' => '/v1/test',
                'expectedController' => 'TestController',
                'expectedAction' => 'handle'
            ),
            array( // #1
                'url' => '/v1',
                'expectedController' => 'N/A',
                'expectedAction' => 'N/A',
                'expectedException' => 'Exception',
                'expectedExceptionCode' => 404
            ),
        );
    }

    /**
     * @dataProvider getLegacyRouteProvider
     *
     * @covers VersionedRouter::getLegacyRoute
     *
     * @param string $url
     * @param string $expectedController
     * @param string $expectedAction
     * @param string|false $expectedException
     * @param integer|false $expectedExceptionCode
     */
    public function testGetLegacyRoute($url, $expectedController, $expectedAction, $expectedException = false, $expectedExceptionCode = false)
    {
        $request = new Request([], ['REQUEST_URI' => $url]);
        $obj = new VersionedRouter('1', array('xyz' => 'abc'));

        try {
            $route = $obj->getLegacyRoute($request);
            $this->assertEquals($expectedController, $route->getController());
            $this->assertEquals($expectedAction, $route->getAction());
        } catch (Exception $ex) {
            if (!$expectedException) {
                throw $ex;
            }
            $this->assertInstanceOf($expectedException, $ex);
            if ($expectedExceptionCode !== false) {
                $this->assertEquals($expectedExceptionCode, $ex->getCode());
            }
        }
    }
}