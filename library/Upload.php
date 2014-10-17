<?php
/**
 * 文件上传处理类  
 * @author tabalt
 */
class Upload {

    /**
     * 错误信息
     * @author tabalt
     * @var string
     */
    protected $errMsg = '';

    /**
     * 上传路径
     * @author tabalt
     * @var string
     */
    public $uploadPath = './';

    /**
     * 类型列表
     * @author tabalt
     * @var array
     */
    public $typeList = array(
        'gif' => 'image/gif', 
        'jpg' => 'image/jpeg', 
        'png' => 'image/png', 
        'jpeg' => 'image/jpeg', 
        'bmp' => 'image/x-ms-bmp', 
        'doc' => 'application/vnd.ms-word', 
        'docx' => 'application/msword', 
        'rar' => 'application/x-rar', 
        'zip' => 'application/zip'
    );

    /**
     * 允许最大长度  2M
     * @author tabalt
     * @var int
     */
    public $maxSize = 2097152;

    /**
     * 是否随机重命名
     * @author tabalt
     * @var boolean
     */
    public $isRandName = true;

    /**
     * 获取上传的错误信息
     * @author tabalt
     * @param int $errNo
     */
    protected function getUploadError($errNo) {
        switch ($errNo) {
            case 7 :
                $errMsg = "文件写入失败！";
                break;
            case 6 :
                $errMsg = "找不到临时文件夹！";
                break;
            case 4 :
                $errMsg = "没有文件被上传！";
                break;
            case 3 :
                $errMsg = "文件只有部分被上传！";
                break;
            case 2 :
                $errMsg = "上传文件的大小超过了 表单中指定的值！";
                break;
            case 1 :
                $errMsg = "上传文件的大小超过了PHP配置文件限制的值！";
                break;
            default :
                $errMsg = '上传失败';
        }
        return $errMsg;
    }

    /**
     * 空构造方法，防止自动调用和类名同名的方法
     * @author tabalt
     */
    public function __construct() {
    	
    }
    
    /**
     * 设置错误信息
     * @author tabalt
     * @param string $errMsg 错误信息
     */
    public function setError($errMsg) {
        $this->errMsg = $errMsg;
    }

    /**
     * 获取错误信息
     * @author tabalt
     * @return string $errMsg 错误信息
     */
    public function getError() {
        return $this->errMsg;
    }

    /**
     * 获取上传文件的信息
     * @author tabalt
     * @param string $field
     * @param boolean $multi
     */
    public function getFileInfo($field, $multi = false) {
        $fieldInfo = isset($_FILES[$field]) ? $_FILES[$field] : false;
        if (empty($fieldInfo)) {
            $this->setError("上传的文件不存在");
            return false;
        }
        $fileInfoList = $fileInfo = array();
        if (is_array($fieldInfo['name']) && ($multi == true)) {
            //多个文件
            foreach (array_keys($fieldInfo['name']) as $key) {
                //'type' => $fieldInfo['type']
                $fileInfo = array(
                    'name' => $fieldInfo['name'][$key], 
                    'tmp_name' => $fieldInfo['tmp_name'][$key], 
                    'size' => $fieldInfo['size'][$key], 
                    'error' => $fieldInfo['error'][$key], 
                    'type' => mime_content_type($fieldInfo['tmp_name'][$key])
                );
                $array = explode('.', $fieldInfo['name'][$key]);
                $fileInfo['ext'] = strtolower($array[count($array) - 1]);
                $fileInfoList[] = $fileInfo;
            }
            return $fileInfoList;
        } else if (is_string($fieldInfo['name']) && ($multi == false)) {
            //一个文件
            //'type' => $fieldInfo['type']
            $fileInfo = array(
                'name' => $fieldInfo['name'], 
                'tmp_name' => $fieldInfo['tmp_name'], 
                'size' => $fieldInfo['size'], 
                'error' => $fieldInfo['error'], 
                'type' => mime_content_type($fieldInfo['tmp_name'])
            );
            $array = explode('.', $fieldInfo['name']);
            $fileInfo['ext'] = strtolower($array[count($array) - 1]);
            return $fileInfo;
        } else {
            $this->setError("上传文件的个数不正确");
            return false;
        }
    }

    /**
     * 上传检测
     * @author tabalt
     * @param array $fileInfo
     */
    public function checkFileInfo($fileInfo) {
        //检查上传 错误
        if ($fileInfo['error'] > 0) {
            $this->setError($this->getUploadError($fileInfo['error']));
            return false;
        }
        //大小判断
        if ($fileInfo['size'] > $this->maxSize) {
            $this->setError("上传文件不能大于" . ($this->maxSize / 1024 / 1024) . "M");
            return false;
        }
        
        //检查限制文件类型
        /*
        if (!array_key_exists($fileInfo['ext'], $this->typeList) || ($fileInfo['type'] != $this->typeList[$fileInfo['ext']])) {
            $this->setError("不允许上传该类型的文件");
            return false;
        }*/
        if (!array_key_exists($fileInfo['ext'], $this->typeList) || !in_array($fileInfo['type'], $this->typeList)) {
            $this->setError("不允许上传该类型的文件");
            return false;
        }
        return true;
    }

    /**
     * 上传一个文件
     * @author tabalt
     * @param array $fileInfo
     */
    public function uploadFile($fileInfo) {
        //检查路径
        if (!file_exists($this->uploadPath) || !is_writeable($this->uploadPath)) {
            $this->setError("上传路径不可写");
            return false;
        }
        if ($this->checkFileInfo($fileInfo)) {
            //设置新文件名
            if ($this->isRandName) {
                $fileName = md5(mt_rand() . date("YmdHis") . mt_rand()) . $fileInfo['ext'];
            } else {
                $fileName = $fileInfo['name'];
            }
            //将文件复制到指定目录
            if (!@move_uploaded_file($fileInfo['tmp_name'], $this->uploadPath)) {
                $this->setError("上传失败");
                return false;
            }
            return $fileName;
        }
    }

    /**
     * 文件上传
     * @author tabalt
     * @param string $field 表单中的文件域的名称
     * @return boolean true/false 上传成功返回true，出错返回false，通过getError()获取错误信息
     */
    public function upload($field, $multi = false) {
        if ($multi) {
            $fileInfoList = $this->getFileInfo($field, true);
            $fileNameList = array();
            foreach ($fileInfoList as $fileInfo) {
                $fileNameList[] = $this->uploadFile($fileInfo);
            }
            return $fileNameList;
        } else {
            $fileInfo = $this->getFileInfo($field, false);
            $fileName = $this->uploadFile($fileInfo);
            return $fileName;
        }
    }

}
