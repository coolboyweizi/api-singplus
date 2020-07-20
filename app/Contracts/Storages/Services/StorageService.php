<?php

namespace SingPlus\Contracts\Storages\Services;

/**
 * interface for external storage service
 */
interface StorageService
{
    /**
     * store given file
     *
     * @param string $filePath  file path.   this file MUST exist
     * @param array $options    options
     *                          - bucket string     (optional) which bucket to store the file
     *                          - prefix string     (optional) prefix of object
     *                          - public bool       (optional) default true
     *                          - mine string       (optional) file minetype
     *
     * @return string|array     - if the returned value is a string, it's the key
     *                          of the file.
     *
     * @throws \Exception       network/storage exception
     */
    public function store(string $filePath, array $options = []) : string;

    /**
     * get file (identified by its identifier) from external storage
     *
     * @param string $key         key for the file, include bucket name
     * @param array $options      options
     *                            - null_for_nonexistence
     *                                              when set to true(default), if the file
     *                                              does not exist, return null. otherwise,
     *                                              exception will be thrown.
     *
     * @return string     - if both content and metadata is false, it will throw exception
     *                            - if only metadata is true, the returned value is a array of
     *                            the metadata.
     *                            - if only content is true, the returned value is a resource,
     *                            it's the content of the file. use stream_get_contents() to read it.
     */
    public function resolve(string $key, array $options = []) : ?string;

    /**
     * get portal of the file (identified by its identifier). typically it could
     * be an http url or something alike.
     *
     * @param string $key        key for the file
     * @param array $options     options
     *                            - inspect   when set to true(default false), no inspection
     *                                        will be performed to check the file's existence.
     *
     * @return string          portal url
     * @throws \Exception
     */
    public function toHttpUrl(?string $key, array $options = []) : ?string;

    /**
     * remove the stored file(identified by its identifier)
     *
     * @param string $key         key for the file to be removed
     * @param array $options      options.
     *                               - inspect  when set to true(default false), no inspection
     *                                          will be performed to check the file's existence.
     *
     * @return void    if there's no exception, the removal succeeded.
     */
    public function remove(string $key, array $options = []);

    /**
     * store by copy the given file already uploaded
     *
     * @param string $source    identifier of source
     * @param array $options    options
     *                          - url_only        when set to true(default), only url
     *                                            of the file will be returned.
     *                          - source_bucket   (optional) which bucket the source file is stored
     *                          - dest_bucket     (optional) which bucket to store the file
     *                          - url             (optional) If the file with this url exists,
     *                                            it will be overwritten.
     *                          - ext             (Optional) extension of the file. it's possible
     *                                            that $file does not have extension suffix.
     *                          - mime            (optional) mime of the file.
     *                          - prefix          (optional) prefix of object
     *
     * @return string|array     - if the returned value is a string, it's the identifier
     *                            of the file.
     *                          - if the returned value is an array, identifier of the
     *                            file should be included, which is keyed by 'id'.
     *
     * @throws \Exception       network/storage exception
     */
    public function copy(string $source, array $options = []);

    /**
     * indicate whether file (identified by its key) exists or not
     *
     * @param string $key       key for the file
     *
     * @return bool
     */
    public function has(string $key) : bool;

    /**
     * Get s3 presigned post object, which be used for client upload directly to s3 server
     *
     * @param string $prefix    work, which will be upload by client, storage key
     * @param ?string $mimeType work mimetype, eg: audio/mp4
     *
     * @return \stdClass        preperty as below:
     *                          - key string    storage key
     *                          - presigned \stdClass
     *                            - formAttributes  array   upload attributes
     *                              - action string   s3 upload target url
     *                              - method string   POST
     *                              - enctype string  specified content type
     *                            - formInputs  array
     */
    public function getS3PresignedPost(string $prefix, ?string $mimeType = null);
}
