<?php
/**
 * 数据映射模型CDs
 * @author xonze|李文祥(xonze@sohu.com)
 * @package XMongo/example/example_4
 */
class CDs extends XMongo
{
    protected $_collection = 'cds';
    
    protected $_autov = array(
            'author' => array(
                    'required' => true,
                    'filter' => 'trim',
            ),
            'title' => array(
                    'required' => true,
                    'filter' => 'trim',
            ),
            'year' => array(
                    'required' => true,
                    'filter' => 'intval',
            ),
    );
    
    /**
     * 根据ID删除CD
     * @param string $id
     * @return boolean
     */
    public function deleteById($id)
    {
        $where = XMongo_Utility::where(XMongo_Db::DEAFAULT_ID,$id);
        
        return parent::delete($where);
    }
}