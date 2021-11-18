<?php
require_once "dbconn.php";
require_once "checklogin_admin.php";
require __DIR__.'/error_slack.php';

// Fetch records from database
$query = "SELECT t.*, SUBSTRING_INDEX(change_name,'|',1) AS n1, SUBSTRING_INDEX(change_name,'|',-1) AS n2, c.*, Srank,
          CONCAT(DATE_FORMAT( since , '%d' ) , '/', DATE_FORMAT( since , '%m' ), '/' , DATE_FORMAT( since , '%Y' ) -543) as since
          FROM t_name as t 
          INNER JOIN `case` as c ON t.idt_case = c.idt_case
          INNER JOIN `rank` as r ON r.idrank = t.idcf001o 
          WHERE id_status = 2 and id_type = 2 ORDER BY idcf001o, gender, thai_idno ASC ";
$result = mysqli_query($conn, $query);
$i = 1;
if (mysqli_num_rows($result) > 0) {
  $delimiter = ",";
  $filename = "p_rename" . "_" . date("Y-m-d") . ".csv"; //ชื่อไฟล์
  //ข้อมูลนายทหารประทวน
  // Create a file pointer
  $fp = fopen("php://memory", "w");
  if ($fp) {
    fwrite($fp, pack("CCC", 0xef, 0xbb, 0xbf)); //ประกาศใช้ UTF-8 ในไฟล์ CSV. ให้แสดงข้อมูลเป็นภาษาไทย
  // Set column headers
  $fields = [
    "ลำดับ",
    "ยศ - ชื่อตัว - ชื่อสกุล",
    "หมายเลขประจำตัวประชาชน",
    "ชื่อตัวเดิม",
    "ชื่อตัวใหม่",
    "ชื่อสกุลเดิม",
    "ชื่อสกุลใหม่",
    "ตั้งแต่",
    "หมายเหตุ",
  ];
  fputcsv($fp, $fields, $delimiter);
  
  // Output each row of the data, format line as csv and write to file pointer

  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $fullname =  $row["Srank"] . " " . $row["fname"] . " " . $row["lname"];
    $old_fname = ($row['change_opt'] == 'ชื่อตัว')?$row["n1"]:'-';
    $new_fname = ($row['change_opt'] == 'ชื่อตัว')?$row["n2"]:'-';
    $old_lname = ($row['change_opt'] == 'ชื่อสกุล')?$row["n1"]:'-';
    $new_lname = ($row['change_opt'] == 'ชื่อสกุล')?$row["n2"]:'-';
    $case  = ($row['idt_case'] == '8')?' ':$row['list_case'];
    $lineData = [
      $i++,
      $fullname,
      "=\"" . $row["thai_idno"] . "\"", // ทำให้เลขปชช เป็นสตริงในไฟล์ CSV.
      $old_fname,
      $new_fname,
      $old_lname,
      $new_lname,
      $row["since"],
      $case,
    ];
    fputcsv($fp, $lineData, $delimiter);
  }
  // Move back to beginning of file
  fseek($fp, 0);

  // Set headers to download file rather than displayed
  header("Content-Encoding: UTF-8");
  header("Content-type: text/csv; charset=UTF-8");
  header('Content-Disposition: attachment; filename="' . $filename . '";');
  echo "\xEF\xBB\xBF"; // UTF-8 BOM
  
  //output all remaining data on a file pointer
  fpassthru($fp);
  fclose($fp);
}
}
exit();
?>
