<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// require APPPATH.'libraries/REST_Controller.php';

class SendEmail extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
	}
	
	public function sendPayEmail(){
		echo '<form action="/sendemail/upLoadCSV" method="post" enctype="multipart/form-data">
			  	<div>
				上传csv文件 <input type="file" name="payment">
			  	</div><div>
			  	  <input class="btn" type="submit" value="保存">
			  	</div>
			  </form>';
	}
	
	public function upLoadCSV(){
		$folder = FCPATH."/static/payment/";
		!file_exists($folder) AND mkdir($folder, 0755, true);
		$fileKey = 'payment';
		$err = '';
		if(isset($_FILES[$fileKey]) && is_uploaded_file($_FILES[$fileKey]['tmp_name'])){
			$fileExt = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
			$fileExt = strtolower($fileExt);
			$fileTmpName = pathinfo($_FILES[$fileKey]['tmp_name'], PATHINFO_BASENAME);
			$fileTmpName = strtolower($fileTmpName);
			$config['upload_path'] = $folder;
			$config['overwrite'] = true;
			$config['allowed_types'] = 'csv';
			$this->load->library('upload', $config);
			$fileName = "payment";
			$config['file_name'] = "{$fileName}.{$fileExt}";
			$this->upload->initialize($config);
			if(!$this->upload->do_upload($fileKey)){
				$err = ' 文件上传失败！';
				$err .= $this->upload->display_errors();
			} else {
				$paymentFileUrl = "/static/payment/{$config['file_name']}";
				$encodedPaymentFileUrl = urlencode($paymentFileUrl);
				echo "文件上传成功,<a href=\"/sendemail/sendBtnClicked/{$encodedPaymentFileUrl}\">***!!点这里发送邮件!!***</a>";
				return;
			}
		} else {
			$err = '没有上传图片';
		}
		echo $err;
	}
	
	public function sendBtnClicked($paymentFileUrl){
		if (empty($paymentFileUrl)){
			echo "发送失败，没有找到文件";
			exit ();
		}
		$this->load->library('email');
		$csvFileUrl = urldecode($paymentFileUrl);
		$csvFileFullUrl = FCPATH.$csvFileUrl;
		if(!file_exists($csvFileFullUrl)){
			echo $paymentFileUrl."文件不存在";
			exit();
		}
		$UserNameAndPassFile = dirname(__FILE__)."/nameandpassword.json";
		if(!file_exists($UserNameAndPassFile)){
			echo $UserNameAndPassFile."文件不存在";
			exit();
		}
		$allUserMailBoxs = dirname(__FILE__)."/allmailbox.json";
		if(!file_exists($allUserMailBoxs)){
			echo $allUserMailBoxs."文件不存在";
			exit();
		}
		$json_string = file_get_contents($UserNameAndPassFile);
		$decodeJson = json_decode($json_string,JSON_UNESCAPED_UNICODE);
		if (empty($decodeJson)){
			echo $UserNameAndPassFile."格式不正确";
			exit();
		}
		$userName = $decodeJson["username"];
		$password = $decodeJson["password"];
		
		$json_string = file_get_contents($allUserMailBoxs);
		$toSendList = json_decode($json_string,JSON_UNESCAPED_UNICODE);
		
		if (empty($toSendList)){
			echo $allUserMailBoxs."格式不正确";
			exit();
		}
		$csvFile = fopen($csvFileFullUrl, 'r');
		$i = 0;
		$emailSendErro = '';
		$config['protocol'] = 'smtp';
		$config['smtp_host'] = 'smtp.exmail.qq.com';
		$config['smtp_user'] = $userName;//这里写上你的邮箱账户
		$config['smtp_pass'] = $password;//这里写上你的邮箱密码
		$config['mailtype'] = 'html';
		$config['validate'] = true;
		$config['crlf']  = "\r\n";
		$config['newline'] = "\r\n";
		$config['smtp_port'] = 25;
		$config['charset'] = 'utf-8';
		$config['wordwrap'] = TRUE;
		
		$this->email->initialize($config);
		while ($eachline = fgetcsv($csvFile)) {//每次读取CSV里面的一行内容
			if ($i < 2){
				//拼接通用的邮件头
			}else{
				//拼接每个人的邮件内容并发送
				$data = eval('return '.iconv('gbk','utf-8',var_export($eachline,true)).';');
				$employeeName = $data[0];
				$employeeEmailAddress = $toSendList[$employeeName];
				$content = <<<CONTENT
<style type="text/css">
td {
	text-align: center;
}
</style>
<table border=1 bordercolor=#000000 style="border-collapse:collapse">
<tbody><tr>
<td rowspan="2">姓名</td>
<td rowspan="2">出勤<br>(天)</td>
<td colspan="8">应发工资</td>
<td colspan="6">扣款项目</td>
<td rowspan="2">应发合计</td>
<td rowspan="2">扣社保<br>(五险)</td>
<td rowspan="2">扣住房<br>公积金</td>
<td rowspan="2">应纳税所得额</td>
<td rowspan="2">扣个税</td>
<td rowspan="2">实发金额</td>
</tr>
<tr>
<td>基本工资</td>
<td>岗位工资</td>
<td>绩效工资</td>
<td>绩效比例</td>
<td>实发绩效</td>
<td>差旅及外驻补贴</td>
<td>其它</td>
<td>小记</td>
<td>不足月</td>
<td>事假</td>
<td>病假</td>
<td>其它</td>
<td>备注</td>
<td>小记</td>
</tr>
<tr>
<td>$data[0]</td>
<td>$data[1]</td>
<td>$data[2]</td>
<td>$data[3]</td>
<td>$data[4]</td>
<td>$data[5]</td>
<td>$data[6]</td>
<td>$data[7]</td>
<td>$data[8]</td>
<td>$data[9]0</td>
<td>$data[10]</td>
<td>$data[11]</td>
<td>$data[12]</td>
<td>$data[13]</td>
<td>$data[14]</td>
<td>$data[15]</td>
<td>$data[16]</td>
<td>$data[17]</td>
<td>$data[18]</td>
<td>$data[19]</td>
<td>$data[20]</td>
<td>$data[21]</td>
</tr>
</tbody></table>
CONTENT;
				$this->email->clear(TRUE);
				
				$this->email->from($userName, 'xx');
				$this->email->to($employeeEmailAddress);
				
				$this->email->subject('工资单');
				$this->email->message($content);
				
				$result = $this->email->send();
				if(!$result){
					$emailSendErro .= "Send Email Error: "."姓名：{$employeeName} 邮箱地址：{$employeeEmailAddress}".$this->email->print_debugger()."<br>";
				}
			}
			$i++;
		}
		fclose($csvFile);
		if (!empty($emailSendErro)){
			echo $emailSendErro;
		}else{
			echo "邮件发送成功";
		}
	}
}