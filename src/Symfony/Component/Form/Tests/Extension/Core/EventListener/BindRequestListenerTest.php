<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\EventListener;

use Symfony\Component\Form\Extension\Core\EventListener\BindRequestListener;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfig;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class BindRequestListenerTest extends \PHPUnit_Framework_TestCase
{
    private $values;

    private $filesPlain;

    private $filesNested;

    /**
     * @var UploadedFile
     */
    private $uploadedFile;

    protected function setUp()
    {
        $path = tempnam(sys_get_temp_dir(), 'sf2');
        touch($path);

        $this->values = array(
            'name' => 'Bernhard',
            'image' => array('filename' => 'foobar.png'),
        );

        $this->filesPlain = array(
            'image' => array(
                'error' => UPLOAD_ERR_OK,
                'name' => 'upload.png',
                'size' => 123,
                'tmp_name' => $path,
                'type' => 'image/png'
            ),
        );

        $this->filesNested = array(
            'error' => array('image' => UPLOAD_ERR_OK),
            'name' => array('image' => 'upload.png'),
            'size' => array('image' => 123),
            'tmp_name' => array('image' => $path),
            'type' => array('image' => 'image/png'),
        );

        $this->uploadedFile = new UploadedFile($path, 'upload.png', 'image/png', 123, UPLOAD_ERR_OK);
    }

    protected function tearDown()
    {
        unlink($this->uploadedFile->getRealPath());
    }

    public function requestMethodProvider()
    {
        return array(
            array('POST'),
            array('PUT'),
            array('DELETE'),
            array('PATCH'),
        );
    }

    /**
     * @dataProvider requestMethodProvider
     */
    public function testBindRequest($method)
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $values = array('author' => $this->values);
        $files = array('author' => $this->filesNested);
        $request = new Request(array(), $values, array(), array(), $files, array(
            'REQUEST_METHOD' => $method,
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('author', null, $dispatcher);
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        $this->assertEquals(array(
            'name' => 'Bernhard',
            'image' => $this->uploadedFile,
        ), $event->getData());
    }

    /**
     * @dataProvider requestMethodProvider
     */
    public function testBindRequestWithEmptyName($method)
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $request = new Request(array(), $this->values, array(), array(), $this->filesPlain, array(
            'REQUEST_METHOD' => $method,
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('', null, $dispatcher);
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        $this->assertEquals(array(
            'name' => 'Bernhard',
            'image' => $this->uploadedFile,
        ), $event->getData());
    }

    /**
     * @dataProvider requestMethodProvider
     */
    public function testBindEmptyRequestToCompoundForm($method)
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $request = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => $method,
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('author', null, $dispatcher);
        $config->setCompound(true);
        $config->setDataMapper($this->getMock('Symfony\Component\Form\DataMapperInterface'));
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        // Default to empty array
        $this->assertEquals(array(), $event->getData());
    }

    /**
     * @dataProvider requestMethodProvider
     */
    public function testBindEmptyRequestToSimpleForm($method)
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $request = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => $method,
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('author', null, $dispatcher);
        $config->setCompound(false);
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        // Default to null
        $this->assertNull($event->getData());
    }

    public function testBindGetRequest()
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $values = array('author' => $this->values);
        $request = new Request($values, array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('author', null, $dispatcher);
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        $this->assertEquals(array(
            'name' => 'Bernhard',
            'image' => array('filename' => 'foobar.png'),
        ), $event->getData());
    }

    public function testBindGetRequestWithEmptyName()
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $request = new Request($this->values, array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('', null, $dispatcher);
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        $this->assertEquals(array(
            'name' => 'Bernhard',
            'image' => array('filename' => 'foobar.png'),
        ), $event->getData());
    }

    public function testBindEmptyGetRequestToCompoundForm()
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $request = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('author', null, $dispatcher);
        $config->setCompound(true);
        $config->setDataMapper($this->getMock('Symfony\Component\Form\DataMapperInterface'));
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        $this->assertEquals(array(), $event->getData());
    }

    public function testBindEmptyGetRequestToSimpleForm()
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        $request = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
        ));

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $config = new FormConfig('author', null, $dispatcher);
        $config->setCompound(false);
        $form = new Form($config);
        $event = new FormEvent($form, $request);

        $listener = new BindRequestListener();
        $listener->preBind($event);

        $this->assertNull($event->getData());
    }
}
