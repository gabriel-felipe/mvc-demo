<?php
namespace Mvc\App;
class View {
    protected $data = array();
    protected $path = null;
    public function render()
    {

        if (!file_exists($this->getPath())) {
            throw new \Exception("View não encontrada", 1);
        }
        ob_start();
		include($this->getPath());
        $content = ob_get_clean();
        return $content;
    }

    public function __toString()
    {
        return $this->render();
    }

    public function __set($key,$value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function __get($key)
    {
        return (isset($this->data[$key])) ? $this->data[$key] : false;
    }

    /**
     * Get the value of Data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the value of Data
     *
     * @param mixed data
     *
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the value of Path
     *
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the value of Path
     *
     * @param mixed path
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

}
