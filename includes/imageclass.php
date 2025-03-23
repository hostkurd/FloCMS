<?php
class ImageUpload extends \Bulletproof\Image {
    /**
     * @var string Base Url of the Website, to be provided or will be generated
     */
    protected $baseUrl;
    protected $mimeTypes = array('jpeg', 'png', 'gif', 'jpg', 'webp');

    /**
     * Returns a JSON format of error string or false if no errors occurred.
     *
     * @return string|false
     */
    public function getJsonError()
    {
      return json_encode(
        array(
          'error' => [
            'title'=>"Error Occured",//$this->error['title'],
            'message'=>$this->getError(),
          ],
        )
      );
    }

    /**
     * Provide Files array if not provided.
     *
     * @param null $files
     *
     * @return $this
     */
    public function setFiles($files)
    {
      if ($files) {
        $this->_files = $files;
      }

      return $this;
    }

    /**
     * Provide Base Url if not provided.
     *
     * @param null $Url
     *
     * @return $this
     */
    public function setBaseUrl($Url = null)
    {
      if ($Url) {
        $this->baseUrl = $Url;
      }

      return $this;
    }

    /**
     * Returns the Base Website URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
      if (!$this->baseUrl) {
        $this->baseUrl = 'https://flocms.com/';
      }

      return $this->baseUrl;
    }

    /**
     * Returns a JSON format of the image width, height, name, mime ...
     *
     * @return string
     */
    public function getJson()
    {
      return json_encode(
        array(
          'name' => $this->name,
          'mime' => $this->mime,
          'height' => $this->height,
          'width' => $this->width,
          //'size' => $this->_files['size'],
          'storage' => $this->storage,
          'path' => $this->path,
          'url' => $this->getBaseUrl().$this->path,
        )
      );
    }

}