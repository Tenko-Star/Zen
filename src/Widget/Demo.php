<?php

class Widget_Demo extends Zen_Page_Widget {
    /**
     * @var Zen_Render
     */
    private $_p;

    /**
     * @var Zen_DB
     */
    private $_db;

    /**
     * @map("/")
     */
    public function render() {
        $db = Zen_DB::getDatabaseInfo();
        $db_info['Have Main Server'] = ($db[Zen_DB::HAVE_MAIN]) ? 'true' : 'false';
        $db_info['Have Cache Server'] = ($db[Zen_DB::HAVE_CACHE]) ? 'true' : 'false';
        $db_info['Total Database'] = $db[Zen_DB::TOTAL];
        $db_info['Total Readable Database'] = $db[Zen_DB::TOTAL_READ];
        $db_info['Total Writeable Database'] = $db[Zen_DB::TOTAL_WRITE];
        $db_info['Total Cache Database'] = $db[Zen_DB::TOTAL_CACHE];
        $db_info['Total Nosql Database'] = $db[Zen_DB::TOTAL_NOSQL];
        $this->_p->db_info = $db_info;
        $this->_p->widget_info = scandir(__ZEN_WIDGET_PATH__);
        $this->_p->plugin_info = Zen_Plugin::getPluginList();
        $this->_p->hello = $this->api()->hello();
        $this->_p->database = $this->testDatabase();

        $this->html('index.php');
    }

    public function testDatabase(){
        $query = $this->_db->select()->from('info')->where('id=?', 1);
        return $this->_db->fetchRow($query);

        //OR
        //$query = $this->_db->select()->from('info')-;
        //return $this->_db->fetchAll($query);
    }

    public function init() {
        $this->_p = Zen_Render::bind('index');
        $this->_db = Zen_DB::get(Zen_DB::AUTH_MAIN);
        $this->_p->setDelimiter(" - ");
    }
}