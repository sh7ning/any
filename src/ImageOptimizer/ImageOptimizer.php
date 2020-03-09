<?php

/**
 * 图片压缩 压图类
 *
 * todo mime apng optimize compress
 *
 * Class ImageOptimizer
 */
class ImageOptimizer
{
    private $optimizers = [
        'jpeg'  => '/data/mozjpeg-3.2/cjpeg',
        'png'   => 'pngquant',
        'gif'   => '/data/giflossy-lossy-1.82.1/src/gifsicle',
    ];

    /**
     * @param $source
     * @param $destination
     * @throws Exception
     */
    public static function compress($source, $destination)
    {
        if (!file_exists($source)) {
            throw new \Exception("文件不存在");
        }

        $optimizer = new static();
        $info = getimagesize($source);
        //todo 转换之前需要检查文件是否已经存在，存在的话会转换失败，比如 png 图片
        switch ($info['mime']) {
            case 'image/jpeg':
                $optimizer->compressJpeg($source, $destination);
                break;
            case 'image/png':
                $optimizer->compressPng($source, $destination);
                break;
            case 'image/gif':
                $optimizer->compressGif($source, $destination);
                break;
            default:
                throw new \Exception('不支持的文件类型');
        }
    }

    protected function compressJpeg($source, $destination)
    {
        //Compression quality (0..100; 5-95 is most useful range, default is 75
        $quality = '60,90'; //格式：60,90 或 75
        // 可选项:
        // -progressive 渐进的jepg图片  -quality %s
        $cmd = sprintf(
            '%s -quality %s -outfile %s %s',
            $this->optimizers['jpeg'],
            $quality,
            $destination,
            escapeshellarg($source)
        );

        $this->runCmd($cmd);
    }

    protected function compressPng($source, $destination)
    {
        // min and max are numbers in range 0 (worst) to 100 (perfect)
        $minQuality = 30;
        $maxQuality = 90;
        // 可选项:
        // --iebug iebug
        $cmd = sprintf(
            '%s --quality=%s-%s --output %s %s 2>&1',
            $this->optimizers['png'],
            $minQuality,
            $maxQuality,
            $destination,
            escapeshellarg($source)
        );

        $this->runCmd($cmd);
    }

    protected function compressGif($source, $destination)
    {
        // lossy argument to quality you want (30 is very light compression, 200 is heavy).
        $quality = 100;
        // 可选项:
        $cmd = sprintf(
            '%s -O3 --lossy=%s -o %s %s',
            $this->optimizers['gif'],
            $quality,
            $destination,
            escapeshellarg($source)
        );

        $this->runCmd($cmd);
    }

    /**
     * @param $cmd
     * @return string
     * @throws RuntimeException
     */
    protected function runCmd($cmd)
    {
        $ret = exec(sprintf('%s 2>&1', $cmd), $output, $returnVar);
        // var_dump($ret);

        if ($returnVar != 0) {
            throw new \RuntimeException(sprintf(
                '图片压缩失败, code: %s, 输出: %s',
                $returnVar,
                var_export($output, true)
            ));
        }

        return $ret;
    }
}
