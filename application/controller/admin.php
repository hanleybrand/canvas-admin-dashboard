<?php

require_once 'canvas.php';
class Admin extends Canvas
{
  public function index() {
    $_SESSION['canvas-admin-dashboard']['institution_id'] = 3;
    $data = array();

    $term_model = $this->loadModel('TermModel');
    $data['terms'] = $term_model->findAll(array('institution_id'=>$_SESSION['canvas-admin-dashboard']['institution_id']));

    if(!count($data['terms'])) {
      header('Location: ' . URL . 'admin/terms');
    } else {
      $this->render('admin/index', $data);
    }
  }

  public function import($canvas_term_id='') {
    $filters = array('term'=>$canvas_term_id);

    if(isset($_POST['filters']['term'])) {
      $filters = $_POST['filters'];
      $canvas_term_id = $_POST['filters']['term'];
    }

    if($canvas_term_id == '') {
      header('Location: ' . URL . 'admin/index');
      return;
    }

    $term_model = $this->loadModel('TermModel');
    $term = $term_model->findOne(array(
      'institution_id'=>$_SESSION['canvas-admin-dashboard']['institution_id'],
      'canvas_term_id'=>$canvas_term_id
    ));

    if(!$term) {
      header('Location: ' . URL . 'admin/index');
      return;
    }

    $report_match = array('accounts'=>false, 'courses'=>false, 'enrollments'=>false, 'xlist'=>false);
    $files = scandir(PATH_UPLOADS);

    foreach($files as $file_name) {
      $report = preg_replace('/' . $_SESSION['canvas-admin-dashboard']['institution_id'] . '_' . $canvas_term_id . '_([^\.]+)\.csv$/', '$1', $file_name);

      if(isset($report_match[$report])) {
        $report_match[$report] = true;
      }
    }

    $data = array('reports'=>$report_match, 'filters'=>$filters);
    $data['terms'] = $term_model->findAll(array('institution_id'=>$_SESSION['canvas-admin-dashboard']['institution_id']));

    $this->render('admin/import', $data);
  }
  
  public function terms() {
    $report_match = array('terms'=>false);
    $files = scandir(PATH_UPLOADS);

    foreach($files as $file_name) {
      $report = preg_replace('/' . $_SESSION['canvas-admin-dashboard']['institution_id'] . '_(terms)\.csv$/', '$1', $file_name);

      if(isset($report_match[$report])) {
        $report_match[$report] = true;
      }
    }

    $data = array('reports'=>$report_match);

    $this->render('admin/import', $data);

  }

  public function users() {
    $report_match = array('users'=>false);
    $files = scandir(PATH_UPLOADS);

    foreach($files as $file_name) {
      $report = preg_replace('/' . $_SESSION['canvas-admin-dashboard']['institution_id'] . '_(users)\.csv$/', '$1', $file_name);

      if(isset($report_match[$report])) {
        $report_match[$report] = true;
      }
    }

    $data = array('reports'=>$report_match);

    $this->render('admin/import', $data);

  }

  public function upload($canvas_term_id='') {
    if(isset($_POST['filters']['term'])) {
      $canvas_term_id=$_POST['filters']['term'];
    }

    $file_name_prefix = $this->report_prefix($canvas_term_id);

    $uploaddir = PATH_UPLOADS;
    foreach ($_FILES as $report_name => $file_data) {
      if ($file_data['tmp_name'] !== '') {
        $file_name = $file_name_prefix . $report_name . '.csv';
        # code...
        $uploadfile = $uploaddir . '/' . $file_name;

        if (move_uploaded_file($file_data['tmp_name'], $uploadfile)) {
          echo "File `$file_name` is valid, and was successfully uploaded.\n";
        } else {
          echo "Problem uploading  `".$file_data['tmp_name']."` ($report_name)\n";exit;
        }

        $import_method = "process_$report_name";
        $this->$import_method($canvas_term_id);
      }
    }

    header("Location: " . URL . "admin/index");
  }

}