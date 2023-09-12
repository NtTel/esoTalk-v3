<?php

if (!defined('IN_ESOTALK')) exit;

ET::$pluginInfo['Upyun'] = array(
  'name' => 'Upyun',
  'description' => 'Chinese Cloud storage',
  'version' => '0.1.0',
  'author' => 'rhyzx',
  'authorEmail' => 'rhyzix@gmail.com',
  'authorURL' => 'https://3dgundam.org',
  'license' => 'MIT'
);

class ETPlugin_Upyun extends ETPlugin {

  public function __construct($rootDirectory)
  {
    parent::__construct($rootDirectory);
    ETFactory::registerController('upyun', 'UpyunController', dirname(__FILE__).'/UpyunController.class.php');
  }

  public function handler_conversationController_renderBefore($sender)
  {
    $sender->addJSFile($this->resource('upyun.js'));
    $sender->addCssFile($this->resource('upyun.css'));
  }
  public function handler_conversationController_getEditControls($sender, &$controls, $id)
  {
    addToArrayString($controls, "imageup", "<a href='javascript:UPyun.imageup(\"$id\");void(0)' title='".T("文件上传")."' class='control-fixed'><i class='icon-paper-clip'></i></a>", 0);

  }
 
  public function handler_format_format($sender)
  {
    $sender->content = preg_replace_callback("/\[upyun\]((?:\w+:\/\/|\/).*?)\[\/upyun\]/i", array($this, "upyunCallback"), $sender->content);
  }

  public function upyunCallback($matches, $expanded = true)
  {
    $upyun = $matches[1];
    $extension = strtolower(pathinfo($upyun, PATHINFO_EXTENSION));
    $url = $upyun;
    $filename = basename($upyun);
    $displayFilename = ET::formatter()->init($filename)->highlight(ET::$session->get("highlight"))->get();


    
    // For images, either show them directly or show a thumbnail.
    if (in_array($extension, array("jpg", "jpeg", "png", "gif"))) {
      return "<a href='".$url."' target='_blank'><img src='".$upyun."' alt='".$filename."' title='".$filename."'></a>";
    }

    // Embed video.
    if (in_array($extension, array("mp4", "mov", "mpg", "avi", "m4v")) and $expanded) {
      return "<video width='400' height='225' controls><source src='".$url."'></video>";
    }

    // Embed audio.
    if (in_array($extension, array("mp3", "mid", "wav")) and $expanded) {
      return "<audio controls><source src='".$url."'></video>";
    }

    $icons = array(
      "pdf" => "file-text-alt",
      "doc" => "file-text-alt",
      "docx" => "file-text-alt",
      "zip" => "archive",
      "rar" => "archive",
      "gz" => "archive"
    );
    $icon = isset($icons[$extension]) ? $icons[$extension] : "file";
    return "<a href='".$url."' class='upyuns' target='_blank'><i class='icon-$icon'></i><span class='filename'>".$displayFilename."</span></a>";
  }


  public function settings($sender)
  {
    $form = ETFactory::make('form');
    $form->action = URL('admin/plugins/settings/Upyun');

    $form->setValue('bucket', C('plugin.upyun.bucket'));
    $form->setValue('secret', C('plugin.upyun.secret'));
    // $form->setValue('expiration', C('plugin.upyun.expiration'));

    if ($form->validPostBack('submit')) {
      $config = array();
      $config['plugin.upyun.bucket'] = $form->getValue('bucket');
      $config['plugin.upyun.secret'] = $form->getValue('secret');

      if (!$form->errorCount()) {
        ET::writeConfig($config);
        $sender->message(T('message.changesSaved'), 'success autoDismiss');
        $sender->redirect(URL('admin/plugins'));
      }
    }
    $sender->data('form', $form);
    return $this->view('settings');
  }
}
