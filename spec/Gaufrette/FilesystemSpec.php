<?php

namespace spec\Gaufrette;

use Gaufrette\Adapter;
use Gaufrette\Exception\StorageFailure;
use Gaufrette\File;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

interface ExtendedAdapter extends \Gaufrette\Adapter,
                          \Gaufrette\Adapter\FileFactory,
                          \Gaufrette\Adapter\StreamFactory,
                          \Gaufrette\Adapter\ChecksumCalculator,
                          \Gaufrette\Adapter\MetadataSupporter,
                          \Gaufrette\Adapter\MimeTypeProvider
{}

class FilesystemSpec extends ObjectBehavior
{
    function let(Adapter $adapter)
    {
        $this->beConstructedWith($adapter);
    }

    function it_is_initializable()
    {
        $this->shouldBeAnInstanceOf('Gaufrette\Filesystem');
        $this->shouldBeAnInstanceOf('Gaufrette\FilesystemInterface');
    }

    function it_gives_access_to_adapter(Adapter $adapter)
    {
        $this->getAdapter()->shouldBe($adapter);
    }

    function it_checks_if_file_exists_using_adapter(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->exists('otherFilename')->willReturn(false);

        $this->has('filename')->shouldReturn(true);
        $this->has('otherFilename')->shouldReturn(false);
    }

    function it_renames_file(Adapter $adapter)
    {
        $adapter->exists('filename')->shouldBeCalled()->willReturn(true);
        $adapter->exists('otherFilename')->shouldBeCalled()->willReturn(false);
        $adapter->rename('filename', 'otherFilename')->shouldBeCalled();

        $this->rename('filename', 'otherFilename');
    }

    function it_fails_when_renamed_source_file_does_not_exist(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(false);

        $this
            ->shouldThrow(new \Gaufrette\Exception\FileNotFound('filename'))
            ->duringRename('filename', 'otherFilename')
        ;
    }

    function it_fails_when_renamed_target_file_exists(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->exists('otherFilename')->willReturn(true);

        $this
            ->shouldThrow(new \Gaufrette\Exception\UnexpectedFile('otherFilename'))
            ->duringRename('filename', 'otherFilename')
        ;
    }

    function it_fails_when_a_storage_failure_happens_during_rename(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->exists('otherFilename')->willReturn(false);
        $adapter->rename('filename', 'otherFilename')->willThrow(StorageFailure::unexpectedFailure('rename', []));

        $this
            ->shouldThrow(StorageFailure::class)
            ->duringRename('filename', 'otherFilename')
        ;
    }

    function it_creates_file_object_for_key(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);

        $this->get('filename')->shouldBeAnInstanceOf('Gaufrette\File');
    }

    function it_does_not_get_file_object_when_file_does_not_exist(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(false);

        $this
            ->shouldThrow(new \Gaufrette\Exception\FileNotFound('filename'))
            ->duringGet('filename')
        ;
    }

    function it_gets_file_object_when_file_does_not_exist_but_can_be_created(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(false);

        $this->get('filename', true)->shouldBeAnInstanceOf('Gaufrette\File');
    }

    function it_delegates_file_instantiation_to_adapter_when_adapter_is_file_factory(ExtendedAdapter $extendedAdapter, File $file)
    {
        $this->beConstructedWith($extendedAdapter);
        $extendedAdapter->exists('filename')->willReturn(true);
        $extendedAdapter->createFile('filename', $this)->willReturn($file);

        $this->get('filename')->shouldBe($file);
    }

    function it_writes_content_to_new_file(Adapter $adapter)
    {
        $adapter->exists('filename')->shouldBeCalled()->willReturn(false);
        $adapter->write('filename', 'some content to write')->shouldBeCalled();

        $this->write('filename', 'some content to write');
    }

    function it_updates_content_of_file(Adapter $adapter)
    {
        $adapter->write('filename', 'some content to write')->shouldBeCalled();

        $this->write('filename', 'some content to write', true);
    }

    function it_does_not_update_content_of_file_when_file_cannot_be_overwriten(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->write('filename', 'some content to write')->shouldNotBeCalled();

        $this
            ->shouldThrow(new \Gaufrette\Exception\FileAlreadyExists('filename'))
            ->duringWrite('filename', 'some content to write')
        ;
    }

    function it_fails_when_write_is_not_successful(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(false);
        $adapter->write('filename', 'some content to write')->shouldBeCalled()->willThrow(StorageFailure::class);

        $this
            ->shouldThrow(StorageFailure::class)
            ->duringWrite('filename', 'some content to write')
        ;
    }

    function it_read_file(Adapter $adapter)
    {
        $adapter->exists('filename')->shouldBeCalled()->willReturn(true);
        $adapter->read('filename')->shouldBeCalled()->willReturn('Some content');

        $this->read('filename')->shouldReturn('Some content');
    }

    function it_does_not_read_file_which_does_not_exist(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(false);

        $this
            ->shouldThrow(new \Gaufrette\Exception\FileNotFound('filename'))
            ->duringRead('filename');
    }

    function it_fails_when_read_is_not_successful(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->read('filename')->willThrow(StorageFailure::class);

        $this
            ->shouldThrow(StorageFailure::class)
            ->duringRead('filename')
        ;
    }

    function it_deletes_file(Adapter $adapter)
    {
        $adapter->exists('filename')->shouldBeCalled()->willReturn(true);
        $adapter->delete('filename')->shouldBeCalled();

        $this->delete('filename');
    }

    function it_does_not_delete_file_which_does_not_exist(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(false);

        $this
            ->shouldThrow(new \Gaufrette\Exception\FileNotFound('filename'))
            ->duringDelete('filename')
        ;
    }

    function it_fails_when_delete_is_not_successful(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->delete('filename')->willThrow(StorageFailure::unexpectedFailure('delete', []));

        $this
            ->shouldThrow(StorageFailure::class)
            ->duringDelete('filename')
        ;
    }

    function it_should_get_all_keys(Adapter $adapter)
    {
        $keys = array('filename', 'filename1', 'filename2');
        $adapter->keys()->willReturn($keys);

        $this->keys()->shouldReturn($keys);
    }

    function it_match_listed_keys_using_specified_pattern(Adapter $adapter)
    {
        $keys = array('filename', 'filename1', 'filename2', 'testKey', 'KeyTest', 'testkey');

        $adapter->keys()->willReturn($keys);
        $adapter->isDirectory(Argument::any())->willReturn(false);

        $this->listKeys()->shouldReturn(
            array(
                'keys' => array('filename', 'filename1', 'filename2', 'testKey', 'KeyTest', 'testkey'),
                'dirs' => array()
            )
        );
        $this->listKeys('filename')->shouldReturn(
            array(
                'keys' => array('filename', 'filename1', 'filename2'),
                'dirs' => array()
            )
        );
        $this->listKeys('Key')->shouldReturn(
            array(
                'keys' => array('KeyTest'),
                'dirs' => array()
            )
        );
    }

    function it_listing_directories_using_adapter_is_directory_method(Adapter $adapter)
    {
        $keys = array('filename', 'filename1', 'filename2', 'testKey', 'KeyTest', 'testkey');

        $adapter->keys()->willReturn($keys);
        $adapter->isDirectory('filename')->willReturn(false);
        $adapter->isDirectory('filename2')->willReturn(false);
        $adapter->isDirectory('KeyTest')->willReturn(false);
        $adapter->isDirectory('testkey')->willReturn(false);

        $adapter->isDirectory('filename1')->willReturn(true);
        $adapter->isDirectory('testKey')->willReturn(true);

        $this->listKeys()->shouldReturn(
            array(
                'keys' => array('filename', 'filename2', 'KeyTest', 'testkey'),
                'dirs' => array('filename1', 'testKey')
            )
        );
        $this->listKeys('filename')->shouldReturn(
            array(
                'keys' => array('filename', 'filename2'),
                'dirs' => array('filename1')
            )
        );
        $this->listKeys('Key')->shouldReturn(
            array(
                'keys' => array('KeyTest'),
                'dirs' => array()
            )
        );
    }

    function it_gets_mtime_of_file_using_adapter(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->mtime('filename')->willReturn(1234567);

        $this->mtime('filename')->shouldReturn(1234567);
    }

    function it_does_not_get_mtime_of_file_which_does_not_exist(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(false);

        $this
            ->shouldThrow(new \Gaufrette\Exception\FileNotFound('filename'))
            ->duringMtime('filename')
        ;
    }

    function it_calculates_file_checksum(Adapter $adapter)
    {
        $adapter->exists('filename')->shouldBeCalled()->willReturn(true);
        $adapter->read('filename')->willReturn('some content');

        $this->checksum('filename')->shouldReturn(md5('some content'));
    }

    function it_does_not_calculate_checksum_of_file_which_does_not_exist(Adapter $adapter)
    {
        $adapter->exists('filename')->shouldBeCalled()->willReturn(false);

        $this
            ->shouldThrow(new \Gaufrette\Exception\FileNotFound('filename'))
            ->duringChecksum('filename');
    }

    function it_delegates_checksum_calculation_to_adapter_when_adapter_is_checksum_calculator(ExtendedAdapter $extendedAdapter)
    {
        $this->beConstructedWith($extendedAdapter);
        $extendedAdapter->exists('filename')->shouldBeCalled()->willReturn(true);
        $extendedAdapter->read('filename')->shouldNotBeCalled();
        $extendedAdapter->checksum('filename')->shouldBeCalled()->willReturn(12);

        $this->checksum('filename')->shouldReturn(12);
    }

    function it_delegates_mime_type_resolution_to_adapter_when_adapter_is_mime_type_provider(ExtendedAdapter $extendedAdapter)
    {
        $this->beConstructedWith($extendedAdapter);
        $extendedAdapter->exists('filename')->willReturn(true);
        $extendedAdapter->mimeType('filename')->willReturn('text/plain');

        $this->mimeType('filename')->shouldReturn('text/plain');
    }

    function it_cannot_resolve_mime_type_if_the_adapter_cannot_provide_it(Adapter $adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $this
            ->shouldThrow(new \LogicException(sprintf('Adapter "%s" cannot provide MIME type', get_class($adapter->getWrappedObject()))))
            ->duringMimeType('filename');
    }
}
