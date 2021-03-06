<?php

// +----------------------------------------------------------------------
// | Simplestart Library
// +----------------------------------------------------------------------
// | 版权所有: http://www.simplestart.cn copyright 2020
// +----------------------------------------------------------------------
// | 开源协议: https://www.apache.org/licenses/LICENSE-2.0.txt
// +----------------------------------------------------------------------
// | 仓库地址: https://github.com/simplestart-cn/start-library
// +----------------------------------------------------------------------

namespace start;

use think\App;
use think\Container;

/**
 * 自定义服务基类
 * Class Service
 * @package start
 */
abstract class Service
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 服务名称
     * @var [type]
     */
    protected $name;

    /**
     * 服务模型
     * @var [type]
     */
    public $model;

    /**
     * Service constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        // 初始化名称
        if (empty($this->name)) {
            // 当前模型名
            $name       = str_replace('\\', '/', static::class);
            $this->name = basename($name);
        }
        // 绑定数据模型
        $namespace = $this->app->getNamespace();
        if (!empty($this->model)) {
            if (class_exists($this->model)) {
                $this->model = Container::getInstance()->make($this->model);
            } else if (class_exists($object = "{$namespace}\\model\\{$this->model}")) {
                $this->model = Container::getInstance()->make($object);
            } else {
                throw_error("Model $this->model does not exist.");
            }
        } else {
            if (class_exists($object = "{$namespace}\\model\\{$this->name}")) {
                $this->model = Container::getInstance()->make($object);
            }
        }
        $this->initialize();
    }

    /**
     * 初始化服务
     * @return $this
     */
    protected function initialize()
    {
        return $this;
    }

    /**
     * 静态实例对象
     * @param array $args
     * @return static
     */
    public static function instance(...$args)
    {
        return Container::getInstance()->make(static::class, $args);
    }

    /**
     * 获取模型
     * @param  [type] $name 模型名称
     * @return [type]       [description]
     */
    public static function model()
    {
        $model = self::instance()->model;
         if (!is_object($model)) {
            throw_error("Model does not exist.");
        }
        return new $model;
    }

    /**
     * 获取列表
     * @param  array  $filter [description]
     * @param  array  $order [description]
     * @return [type]         [description]
     */
    public static function getList($filter = [], $order = [])
    {
        $model = self::model();
        return $model->list($filter, $order);
    }

    /**
     * 获取分页
     * @param  array  $filter [description]
     * @param  array  $order [description]
     * @return [type]         [description]
     */
    public static function getPage($filter = [], $order = [])
    {
        $model = self::model();
        return $model->page($filter, $order);
    }

    /**
     * 获取详情
     * @param  array  $filter [description]
     * @return [type]         [description]
     */
    public static function getInfo($filter, $with = [])
    {
        $model = self::model();
        return $model->info($filter, $with);
    }

    /**
     * 创建记录
     * @param  [type] $input [description]
     * @return [type]        [description]
     */
    public static function create($input)
    {
        $model = self::model();
        if ($model->save(self::inputFilter($input))) {
            return $model;
        } else {
            throw_error('create fail');
        }
    }

    /**
     * 更新记录
     * @param  [type] $input [description]
     * @return [type]        [description]
     */
    public static function update($input)
    {
        $model = self::model();
        $pk    = $model->getPk();
        if (!isset($input[$pk]) || empty($input[$pk])) {
            throw_error("$pk can not empty");
        }
        $model = $model->find($input[$pk]);
        if ($model->save(self::inputFilter($input))) {
            return $model;
        } else {
            throw_error('update fail');
        }
    }

    /**
     * 过滤自动更新字段
     */
    private static function inputFilter($input)
    {
        if (isset($input['create_time'])) {
            unset($input['create_time']);
        }

        if (isset($input['update_time'])) {
            unset($input['update_time']);
        }

        return $input;
    }

    /**
     * 删除记录
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    public static function remove($filter)
    {
        if (is_string($filter) && strstr($filter, ',') !== false) {
            $filter = explode(',', $filter);
        }
        $model = self::model();
        if (!is_array($filter)) {
            return $model->find($filter)->remove();
        } else {
            $list = $model->where($model->getPk(), 'in', $filter)->select();
            foreach ($list as $item) {
                $item->remove();
            }
            return true;
        }
    }

    /**
     * 开启事务(待升级为异步事务)
     * @return [type] [description]
     */
    public static function startTrans()
    {
        \think\facade\Db::startTrans();
    }

    /**
     * 事务提交(待升级为异步事务)
     * @return [type] [description]
     */
    public static function startCommit()
    {
        \think\facade\Db::commit();
    }

    /**
     * 事务回滚(待升级为异步事务)
     * @return [type] [description]
     */
    public static function startRollback()
    {
        \think\facade\Db::rollback();
    }

}
