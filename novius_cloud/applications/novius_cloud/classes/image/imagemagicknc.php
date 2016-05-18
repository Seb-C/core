<?php

/**
 * Class Image_Imagemagickfixed
 * Version Novius Cloud d'ImageMagick
 *
 * @Author  Pascal, Julian
 */
class Image_Imagemagicknc extends \Fuel\Core\Image_Imagemagick
{
    protected $image_temp = null;
    protected $accepted_extensions = array('png', 'gif', 'jpg', 'jpeg');
    protected $size_cache = null;
    protected $im_path = null;

    public function load($filename, $return_data = false, $force_extension = false)
    {
        // First check if the filename exists
        $filename = realpath($filename);
        $return   = array(
            'filename'    => $filename,
            'return_data' => $return_data
        );
        if (file_exists($filename)) {
            // Check the extension
            $ext = $this->check_extension($filename, false, $force_extension);
            if ($ext !== false) {
                $return = array_merge($return, array(
                    'image_fullpath'  => $filename,
                    'image_directory' => dirname($filename),
                    'image_filename'  => basename($filename),
                    'image_extension' => $ext
                ));
                if (!$return_data) {
                    $this->image_fullpath  = $filename;
                    $this->image_directory = dirname($filename);
                    $this->image_filename  = basename($filename);
                    $this->image_extension = $ext;
                }
            } else {
                throw new \RuntimeException("The library does not support this filetype for $filename.");
            }
        } else {
            throw new \OutOfBoundsException("Image file $filename does not exist.");
        }

        $force_extension = pathinfo($filename, PATHINFO_EXTENSION);

        //parent::load($filename, $return_data, $force_extension);

        $this->clear_sizes();
        if (empty($this->image_temp)) {
            do {
                $this->image_temp = $this->config['temp_dir'].substr($this->config['temp_append'].md5(time() * microtime()), 0, 32).'.'.$force_extension;
            } while (file_exists($this->image_temp));
        } elseif (file_exists($this->image_temp)) {
            $this->debug('Removing previous temporary image.');
            unlink($this->image_temp);
        }
        $this->debug('Temp file: '.$this->image_temp);
        if (!file_exists($this->config['temp_dir']) || !is_dir($this->config['temp_dir'])) {
            throw new \RuntimeException("The temp directory that was given does not exist.");
        } elseif (!touch($this->config['temp_dir'].$this->config['temp_append'].'_touch')) {
            throw new \RuntimeException("Could not write in the temp directory.");
        }

        \File::copy($filename, $this->image_temp);

        return $this;
    }

    public function save($filename = null, $permissions = null)
    {
        $this->run_queue();
        $this->add_background();

        copy($this->image_temp, $filename);

        return $this;
    }
}
