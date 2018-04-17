<?php

namespace Tree6bee\Any\Helpers;

use Exception;
use Throwable;

/**
 * 视图类
 *
 * 版本依赖:
 *      - Throwable PHP 7
 */
class View
{
    public static function show($__view = null, array $__data = array())
    {
        return (new static)->make($__view, $__data);
    }

    protected function make($__view = null, array $__data = array())
    {
        $obLevel = ob_get_level();
        ob_start();
        extract($__data, EXTR_SKIP);
        try {
            include $__view;
        } catch (Exception $e) {
            $this->handleViewException($e, $obLevel);
        } catch (Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    protected function handleViewException(Exception $e, $obLevel)
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
