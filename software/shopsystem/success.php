<?php
include_once('../globals/db_connection.php');
include_once('../database/db_functions.php');
include_once('../membership/session_start.php');
if(isset($_GET['item'])){
  $num=$_GET['item'];
  $input=array(
    'owner'=>$_SESSION['logged_user']
  );
  $condition=array(
    'id'=>$num
  );
  edit_row(database_connection\get::arr(),'products',$input,$condition);
}
?>
