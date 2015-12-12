<?php namespace BackBlazeB2;

use BackBlazeB2\Exceptions\BackBlazeException;
use BackBlazeB2\Exceptions\NotAuthorizedException;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class Client
{

    /**
     * @var string
     */
    protected $authorizeUrl = 'https://api.backblaze.com/b2api/v1/b2_authorize_account';
    /**
     * @var string
     */
    protected $accountId;
    /**
     * @var
     */
    protected $apiUrl;
    /**
     * @var
     */
    protected $downloadUrl;
    /**
     * @var
     */
    protected $authToken;
    /**
     * @var Guzzle
     */
    protected $guzzle;

    /**
     * Client constructor.
     * @param $accountId
     * @param $applicationKey
     */
    public function __construct($accountId, $applicationKey)
    {
        $this->accountId = $accountId;
        $this->guzzle = new Guzzle();
        $this->authorize_account($accountId, $applicationKey);
    }

    /**
     * @param $accountId
     * @param $applicationKey
     * @throws NotAuthorizedException
     */
    public function authorize_account($accountId, $applicationKey)
    {

        try {
            $res = $this->guzzle->get($this->authorizeUrl, [
                'headers' => [
                    "Accept"        => "application/json",
                    "Authorization" => "Basic " . base64_encode($accountId . ":" . $applicationKey)
                ]
            ]);

            $json = json_decode($res->getBody());
            $this->accountId = $json->accountId;
            $this->apiUrl = $json->apiUrl;
            $this->authToken = $json->authorizationToken;
            $this->downloadUrl = $json->downloadUrl;

        } catch (ClientException $e) {
            throw new NotAuthorizedException($e->getMessage());
        }
    }

    /**
     * Create a new bucket.
     * @see https://www.backblaze.com/b2/docs/b2_create_bucket.html
     * @param $bucketName
     * @param $public
     * @return string
     * @throws BackBlazeException
     */
    public function createBucket($bucketName, $public = false)
    {
        $data = [
            "accountId"  => $this->accountId,
            "bucketName" => $bucketName,
            "bucketType" => ($public) ? "allPublic" : "allPrivate"
        ];

        return $this->post('/b2api/v1/b2_create_bucket', $data);
    }

    /**
     * @param $uri
     * @param $data
     * @return mixed
     * @throws BackBlazeException
     */
    protected function post($uri, $data)
    {
        try {
            $res = $this->guzzle->post($uri, [
                'base_uri' => $this->apiUrl,
                'headers'  => [
                    "Accept"        => "application/json",
                    "Authorization" => $this->authToken,
                    "Content-Type:" => "application/json"
                ],
                'json'     => $data
            ]);

            return json_decode($res->getBody());
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            throw new BackBlazeException($response->message);
        } catch (ServerException $e) {
            throw new BackBlazeException("BackBlaze Server Error:" . $e->getMessage());
        }
    }

    /**
     * Delete a bucket.
     * @see https://www.backblaze.com/b2/docs/b2_delete_bucket.html
     * @param $bucketId
     * @return string
     * @throws BackBlazeException
     */
    public function deleteBucket($bucketId)
    {
        $data = [
            "accountId" => $this->accountId,
            "bucketId"  => $bucketId
        ];

        return $this->post('/b2api/v1/b2_delete_bucket', $data);
    }

    /**
     * Delete a version of a file.
     * @see https://www.backblaze.com/b2/docs/b2_delete_file_version.html
     * @param $fileId
     * @param $fileName
     * @return string
     * @throws BackBlazeException
     */
    public function deleteFileVersion($fileId, $fileName)
    {
        return $this->post('/b2api/v1/b2_delete_file_version', compact($fileId, $fileName));
    }

    /**
     * Download a file by its Id.
     * @param $fileId
     * @return null
     */
    public function downloadFileById($fileId)
    {
        return $this->get($this->downloadUrl . '/b2api/v1/b2_download_file_by_id?fileId=' . $fileId);
    }

    /**
     * Used to Download files from BackBlze
     * @param $uri
     * @return mixed
     * @throws BackBlazeException
     */
    protected function get($uri)
    {
        try {
            $res = $this->guzzle->get($uri, [
                'headers' => [
                    "Authorization" => $this->authToken,
                ],
            ]);

            return $res->getBody()->getContents();
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            throw new BackBlazeException($response->message);
        } catch (ServerException $e) {
            throw new BackBlazeException("BackBlaze Server Error");
        }
    }

    /**
     * @param $bucketName
     * @param $fileName
     * @return null
     */
    public function downloadFileByName($bucketName, $fileName)
    {
        return $this->get($this->downloadUrl . '/file/' . $bucketName . '/' . $fileName);
    }

    /**
     * @param $fileId
     * @return string
     * @throws BackBlazeException
     */
    public function getFileInfo($fileId)
    {
        return $this->post('/b2api/v1/b2_get_file_info', compact('fileId'));
    }

    /**
     * @param $bucketId
     * @param $fileName
     * @return string
     * @throws BackBlazeException
     */
    public function hideFile($bucketId, $fileName)
    {
        return $this->post('/b2api/v1/b2_hide_file', compact('bucketId', 'fileName'));
    }

    /**
     * @return string
     * @throws BackBlazeException
     */
    public function listBuckets()
    {
        $data = [
            "accountId" => $this->accountId
        ];

        return $this->post('/b2api/v1/b2_list_buckets', $data);
    }

    /**
     * @param $bucketId
     * @return string
     * @throws BackBlazeException
     */
    public function listFileNames($bucketId)
    {
        return $this->post('/b2api/v1/b2_list_file_names', compact('bucketId'));
    }

    /**
     * @param $bucketId
     * @return string
     * @throws BackBlazeException
     */
    public function listFileVersions($bucketId)
    {
        return $this->post('/b2api/v1/b2_list_file_versions', compact('bucketId'));
    }

    /**
     * @param $bucketId
     * @param $public
     * @return string
     * @throws BackBlazeException
     */
    public function updateBucket($bucketId, $public)
    {
        $data = [
            "accountId"  => $this->accountId,
            "bucketId"   => $bucketId,
            "bucketType" => ($public) ? "allPublic" : "allPrivate"
        ];

        return $this->post('/b2api/v1/b2_update_bucket', $data);
    }

    /**
     * Uploads a file to a given bucket including optional file meta.
     * @see https://www.backblaze.com/b2/docs/b2_upload_file.html
     *
     * @param $fileName
     * @param $pathToFile
     * @param $bucketId
     * @param string $contentType
     * @param array $metaData
     * @return mixed
     * @throws BackBlazeException
     */
    public function uploadFile($fileName, $pathToFile, $bucketId, $contentType = "text/plain", $metaData = [])
    {
        $uploadData = $this->getUploadUrl($bucketId);
        $handle = fopen($pathToFile, 'r');
        $file = fread($handle, filesize($pathToFile));
        try {
            $res = $this->guzzle->post($uploadData->uploadUrl, [
                'headers' => $this->prepareMeta($metaData, [
                    "Authorization"     => $uploadData->authorizationToken,
                    "X-Bz-File-Name"    => $fileName,
                    "Content-Type"      => $contentType,
                    "X-Bz-Content-Sha1" => sha1_file($pathToFile),
                ]),
                'body'    => $file
            ]);

            return json_decode($res->getBody());
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            throw new BackBlazeException($response->message);
        } catch (ServerException $e) {
            throw new BackBlazeException($e->getMessage());
        }
    }

    /**
     * @param $bucketId
     * @return string
     * @throws BackBlazeException
     */
    public function getUploadUrl($bucketId)
    {
        return $this->post('/b2api/v1/b2_get_upload_url', compact('bucketId'));
    }

    /**
     * Prepares meta into header array.
     * @param array $meta
     * @param array $headers
     * @return array
     */
    private function prepareMeta(Array $meta, Array $headers)
    {
        $meta_output = [];
        foreach ($meta as $key => $value) {
            $meta_output["X-Bz-Info-" . $key] = urlencode($value);
        }

        return array_merge($meta_output, $headers);
    }
}