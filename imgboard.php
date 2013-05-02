<?php
date_default_timezone_set("UTC");
//PLEASE EDIT THIS!
$db_username = "";
$db_password = "";
$db_database = "";
$db_host = "localhost";
$db_prefix = "";

function randomPassword() {
	$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
	$pass = array(); //remember to declare $pass as an array
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < 8; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass); //turn the array into a string
}

function processString($conn, $string, $thread = 0)
{
	$new = $string;
	$lines = explode("\n", $new);
	$new = "";
	foreach ($lines as $line)
	{
		if ((substr($line, 0, 8) == "&gt;&gt;") && (is_numeric(substr($line, 8))))
		{
			$result = mysqli_query($conn, "SELECT * FROM posts WHERE id='".substr($line, 8)."';");

			if (mysqli_num_rows($result) == 1)
			{
				$row = mysqli_fetch_assoc($result);
				if ($row['resto'] != 0)
				{
					if ($thread)
					{
						$new .= '<a href="../res/'.$row['resto'].'.html#p'.$row['id'].'" class="quotelink">'.$line.'</a><br />';
					} else {
						$new .= '<a href="./res/'.$row['resto'].'.html#p'.$row['id'].'" class="quotelink">'.$line.'</a><br />';
					}
				} else {
					if ($thread)
					{
						$new .= '<a href="../res/'.$row['id'].'.html#p'.$row['id'].'" class="quotelink">'.$line.'</a><br />';
					} else {
						$new .= '<a href="./res/'.$row['id'].'.html#p'.$row['id'].'" class="quotelink">'.$line.'</a><br />';
					}
				}
			} else {
				$new .= "<span class='quote'>".$line."</span><br />";
			}
		} elseif (substr($line, 0, 4) == "&gt;")
		{
			$new .= "<span class='quote'>".$line."</span><br />";
		} else {
			$rurl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
			if(preg_match($rurl, $line, $url)) {
				$new .= preg_replace($rurl, '<a href="'.$url[0].'">'.$url[0].'</a> ', $line)."<br />";
			} else {
				$new .= $line."<br />";
			}
		}
	}
	return $new;
}

function preprocessString($conn, $string)
{
	$new = htmlspecialchars($string);
	$new = str_replace("\r", "", $new);
	$new = mysqli_real_escape_string($conn, $new);
	return $new;
}

function generateView($threadno = 0)
{
	global $db_username, $db_database, $db_host, $db_password, $db_prefix;
	$conn = mysqli_connect($db_host, $db_username, $db_password, $db_database);
	if ($threadno != 0)
	{
		$file = "<html><head><title>Mitsuba test</title><link rel='stylesheet' href='../stylesheet.css'>";
		$file .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
		$file .= "</head><body>";
		$file .= '<div class="postingMode desktop">Posting mode: Reply</div>';
		$file .= '<div class="navLinks desktop">[<a href=".././" accesskey="a">Return</a>]</div>';
		$file .= '<center><form name="post" action="../imgboard.php" method="post" enctype="multipart/form-data">';
	} else {
		$file = "<html><head><title>Mitsuba test</title><link rel='stylesheet' href='./stylesheet.css'>";
		$file .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
		$file .= "</head><body>";
		$file .= '<center><form name="post" action="./imgboard.php" method="post" enctype="multipart/form-data">';
	}
	$file .= '<input type="hidden" name="MAX_FILE_SIZE" value="2097152">
		<input type="hidden" name="mode" value="regist">
		<table class="postForm hideMobile" id="postForm">
		<tbody>
		<tr>
		<td>Name</td>
		<td><input name="name" type="text"></td>
		</tr>
		<tr>
		<td>E-mail</td>
		<td><input name="email" type="text"></td>
		</tr>
		<tr>
		<td>Subject</td>
		<td><input name="sub" type="text">';
	if ($threadno != 0)
	{
		$file .= '<input type="hidden" name="resto" value="'.$threadno.'" />';
	}
	$file .= '<input type="submit" value="Submit"></td>
		</td>
		<tr>
		<td>Comment</td>
		<td><textarea name="com" cols="48" rows="4" wrap="soft"></textarea></td>
		</tr>
		<tr>
		<td>File</td>
		<td><input id="postFile" name="upfile" type="file"><div id="fileError"></div></td>
		</tr>
		<tr>
		<td>Password</td>
		<td><input id="postPassword" name="pwd" type="password" maxlength="8"> <span class="password">(Password used for deletion)</span></td>
		</tr>
		<tr class="rules">
		<td colspan="2">
		<ul class="rules">
		<li>Supported file types are: GIF, JPG, PNG</li>
		<li>Maximum file size allowed is 2048 KB.</li>
		<li>Images greater than 250x250 pixels will be thumbnailed.</li>
		</ul>
		</td>
		</tr>
		</tbody>
		</table>
		</form></center>';
	$file .= "<hr />";
	if ($threadno != 0)
	{
		$file .= '<form name="delform" id="delform" action="../imgboard.php" method="post"><div class="board">';
	} else {
		$file .= '<form name="delform" id="delform" action="./imgboard.php" method="post"><div class="board">';
	}

	if ($threadno != 0)
	{
		$result = mysqli_query($conn, "SELECT * FROM ".$db_prefix."posts WHERE id=".$threadno.";");
	} else {
		$result = mysqli_query($conn, "SELECT * FROM ".$db_prefix."posts WHERE resto=0 ORDER BY lastbumped DESC");
	}

	while ($row = mysqli_fetch_assoc($result))
	{
		$file .= '<div class="thread" id="t'.$row['id'].'">';
		$file .= '<div class="postContainer opContainer" id="pc'.$row['id'].'">';
		$file .= '<div id="p'.$row['id'].'" class="post op">';
		if ($row['filename'] == "deleted")
		{
			$file .= '<div class="file" id="f'.$row['id'].'">';
			$file .= '<div class="fileInfo">';
			if ($threadno != 0)
			{
				$file .= '<span class="fileText" id="fT'.$row['id'].'">File: <b>deleted</b></span>';
			} else {
				$file .= '<span class="fileText" id="fT'.$row['id'].'">File: <b>deleted</b></span>';
			}
			$file .= '</div>';
			if ($threadno != 0)
			{
				$file .= '<a class="fileThumb" target="_blank"><img src="../deleted.gif"></a>';
			} else {
				$file .= '<a class="fileThumb" target="_blank"><img src="./deleted.gif"></a>';
			}
			$file .= '</div>';
		} else {
			$file .= '<div class="file" id="f'.$row['id'].'">';
			$file .= '<div class="fileInfo">';
			if ($threadno != 0)
			{
				$file .= '<span class="fileText" id="fT'.$row['id'].'">File: <a href="../src/'.$row['filename'].'" target="_blank">'.$row['filename'].'</a>-(<span title="'.$row['orig_filename'].'">'.$row['orig_filename'].'</span>)</span>';
			} else {
				$file .= '<span class="fileText" id="fT'.$row['id'].'">File: <a href="./src/'.$row['filename'].'" target="_blank">'.$row['filename'].'</a>-(<span title="'.$row['orig_filename'].'">'.$row['orig_filename'].'</span>)</span>';
			}
			$file .= '</div>';
			$fileparts = explode('.',$row['filename']);
			if ($threadno != 0)
			{
				$file .= '<a class="fileThumb" href="../src/'.$row['filename'].'" target="_blank"><img src="../src/thumb/'.$fileparts[0].'.jpg"></a>';
			} else {
				$file .= '<a class="fileThumb" href="./src/'.$row['filename'].'" target="_blank"><img src="./src/thumb/'.$fileparts[0].'.jpg"></a>';
			}
			
			$file .= '</div>';
		}
		$file .= '<div class="postInfo desktop" id="pi'.$row['id'].'">';
		$file .= '<input type="checkbox" name="'.$row['id'].'" value="delete">';
		$file .= '<span class="subject">'.$row['subject'].'</span>';
		if (!empty($row['email'])) {
			$file .= '<span class="nameBlock"><span class="name"><a href="mailto:'.$row['email'].'" class="useremail">'.$row['name'].'</a></span></span>';
		} else {
			$file .= '<span class="nameBlock"><span class="name">'.$row['name'].'</span></span>';
		}
		$file .= '<span class="dateTime">'.date("d/m/Y (D) H:i:s", $row['date']).'</span>';
	
		if ($threadno != 0)
		{
			$file .= '<span class="postNum"><a href="./res/'.$row['id'].'.html#p'.$row['id'].'" title="Highlight this post">No.</a><a href="./res/'.$row['id'].'.html#q'.$row['id'].'" title="Quote this post">'.$row['id'].'</a></span>';
		} else {
			$file .= '<span class="postNum"><a href="./res/'.$row['id'].'.html#p'.$row['id'].'" title="Highlight this post">No.</a><a href="./res/'.$row['id'].'.html#q'.$row['id'].'" title="Quote this post">'.$row['id'].'</a> &nbsp; <span>[<a href="./res/'.$row['id'].'.html" class="replylink">Reply</a>]</span></span>';
		}
		$file .= '</div>';
		
		
		
		$file .= '<blockquote class="postMessage" id="m'.$row['id'].'">';
		$file .= processString($conn, $row['comment'], $threadno != 0);
		$file .= '</blockquote>';
		
		
		
		$file .= '</div>';
		$file .= '</div>';
		if ($threadno != 0)
		{
			$posts = mysqli_query($conn, "SELECT * FROM ".$db_prefix."posts WHERE resto=".$row['id']." ORDER BY id ASC");
		} else {
		$posts = mysqli_query($conn, "SELECT COUNT(*) FROM ".$db_prefix."posts WHERE resto=".$row['id']." ORDER BY id ASC");
		$row1 = mysqli_fetch_row($posts);
		if ($row1[0] == 0)
		{
			$file .= '</div><hr />';
			continue;
		}
		if ($row1[0] > 3)
		{
			$file .= '<span class="summary desktop">'.($row1[0]-3).' posts omitted. Click <a href="./res/'.$row['id'].'.html" class="replylink">here</a> to view.</span>';
		}
		$offset = 0;
		if ($row1[0] > 3)
		{
			$offset = $row1[0] - 3;
			$posts = mysqli_query($conn, "SELECT * FROM ".$db_prefix."posts WHERE resto=".$row['id']." ORDER BY id ASC LIMIT ".$offset.",3");
			
		}
		}
		while ($row2 = mysqli_fetch_assoc($posts))
		{
			$file .= '<div class="postContainer replyContainer" id="pc'.$row2['id'].'">';
			$file .= '<div class="sideArrows" id="sa'.$row2['id'].'">&gt;&gt;</div>';
			$file .= '<div id="p'.$row2['id'].'" class="post reply">';
			$file .= '<div class="postInfo desktop" id="pi'.$row2['id'].'">';
			$file .= '<input type="checkbox" name="'.$row2['id'].'" value="delete">';
			$file .= '<span class="subject">'.$row2['subject'].'</span>';
			if (!empty($row2['email'])) {
				$file .= '<span class="nameBlock"><span class="name"><a href="mailto:'.$row2['email'].'" class="useremail">'.$row2['name'].'</a></span></span>';
			} else {
				$file .= '<span class="nameBlock"><span class="name">'.$row2['name'].'</span></span>';
			}
			$file .= '<span class="dateTime">'.date("d/m/Y (D) H:i:s", $row2['date']).'</span>';
			if ($threadno != 0)
			{
				$file .= '<span class="postNum"><a href="../res/'.$row2['resto'].'.html#p'.$row2['id'].'" title="Highlight this post">No.</a><a href="../res/'.$row2['resto'].'.html#q'.$row2['id'].'" title="Quote this post">'.$row2['id'].'</a> &nbsp;</span></span>';
			} else {
				$file .= '<span class="postNum"><a href="./res/'.$row2['resto'].'.html#p'.$row2['id'].'" title="Highlight this post">No.</a><a href="./res/'.$row2['resto'].'.html#q'.$row2['id'].'" title="Quote this post">'.$row2['id'].'</a> &nbsp;</span></span>';
			}
			$file .= '</div>';
			if (!empty($row2['filename']))
			{
				if ($row2['filename'] == "deleted")
				{
					$file .= '<div class="file" id="f'.$row2['id'].'">';
					$file .= '<div class="fileInfo">';
					if ($threadno != 0)
					{
						$file .= '<span class="fileText" id="fT'.$row2['id'].'">File: <b>deleted</b></span>';
					} else {
						$file .= '<span class="fileText" id="fT'.$row2['id'].'">File: <b>deleted</b></span>';
					}
					$file .= '</div>';
					if ($threadno != 0)
					{
						$file .= '<a class="fileThumb" target="_blank"><img src="../deleted.gif"></a>';
					} else {
						$file .= '<a class="fileThumb" target="_blank"><img src="./deleted.gif"></a>';
					}
				
					$file .= '</div>';
				} else {
					$file .= '<div class="file" id="f'.$row2['id'].'">';
					$file .= '<div class="fileInfo">';
					if ($threadno != 0)
					{
						$file .= '<span class="fileText" id="fT'.$row2['id'].'">File: <a href="../src/'.$row2['filename'].'" target="_blank">'.$row2['filename'].'</a> (<span title="'.$row2['orig_filename'].'">'.$row2['orig_filename'].'</span>)</span>';
					} else {
						$file .= '<span class="fileText" id="fT'.$row2['id'].'">File: <a href="./src/'.$row2['filename'].'" target="_blank">'.$row2['filename'].'</a> (<span title="'.$row2['orig_filename'].'">'.$row2['orig_filename'].'</span>)</span>';
					}
					$file .= '</div>';
					$fileparts = explode('.',$row2['filename']);
					if ($threadno != 0)
					{
						$file .= '<a class="fileThumb" href="../src/'.$row2['filename'].'" target="_blank"><img src="../src/thumb/'.$fileparts[0].'.jpg"></a>';
					} else {
						$file .= '<a class="fileThumb" href="./src/'.$row2['filename'].'" target="_blank"><img src="./src/thumb/'.$fileparts[0].'.jpg"></a>';
					}
				
					$file .= '</div>';
				}
			}
			
			$file .= '<blockquote class="postMessage" id="m'.$row2['id'].'">';
			$file .= processString($conn, $row2['comment'], $threadno != 0);
			$file .= '</blockquote>';
			
			$file .= '</div>';
			
			
			
			$file .= '</div>';
		}
		
		$file .= '</div>';
		$file .= '<hr />';
	}
	mysqli_close($conn);
	$file .= "</div>";
	$file .= '<div class="deleteform desktop">
		<input type="hidden" name="mode" value="usrdel">Delete Post [<input type="checkbox" name="onlyimgdel" value="on">File Only] Password <input type="password" id="delPassword" name="pwd" maxlength="8"> 
		<input type="submit" value="Delete"></div>';
	$file .= "</form>";
	$file .= "</body></html>";
	if ($threadno != 0)
	{
		$handle = fopen("./res/".$threadno.".html", "w");
	} else {
		$handle = fopen("./index.html", "w");
	}
	fwrite($handle, $file);
	fclose($handle);
}

function updateThreads()
{
	global $db_username, $db_database, $db_host, $db_password, $db_prefix;
	$conn = mysqli_connect($db_host, $db_username, $db_password, $db_database);
	$result = mysqli_query($conn, "SELECT id FROM ".$db_prefix."posts WHERE resto=0");
	while ($row = mysqli_fetch_assoc($result))
	{
		generateView($row['id']);
	}
	mysqli_close($conn);
}

function regenThumbnails()
{
	global $db_username, $db_database, $db_host, $db_password, $db_prefix;
	$conn = mysqli_connect($db_host, $db_username, $db_password, $db_database);
	$result = mysqli_query($conn, "SELECT filename, resto FROM ".$db_prefix."posts");
	while ($row = mysqli_fetch_assoc($result))
	{
		if ((!empty($row['filename'])) && ($row['filename'] != "deleted"))
		{
			$fileparts = explode(".", $row['filename']);
			if ($row['resto'] != 0)
			{
				thumb("./src/", $fileparts[0], ".".$fileparts[1], 125);
			} else {
				thumb("./src/", $fileparts[0], ".".$fileparts[1]);
			}
		}
	}
	mysqli_close($conn);
}

function thumb($path,$tim,$ext,$s=250){
	if(!function_exists("ImageCreate")||!function_exists("ImageCreateFromJPEG"))return;
	$fname=$path.$tim.$ext;
	$thumb_dir = './src/thumb/';	 //thumbnail directory
	$width	 = $s;			//output width
	$height	= $s;			//output height
	// width, height, and type are aquired
	$size = GetImageSize($fname);
	try {
		switch ($size[2]) {
			case 1 :
				if(!function_exists("ImageCreateFromGIF"))return;
				$im_in = ImageCreateFromGIF($fname);
				if(!$im_in){return -1;}
				break;
			case 2 : $im_in = ImageCreateFromJPEG($fname);
				if(!$im_in){return -1;}
				break;
			case 3 :
				if(!function_exists("ImageCreateFromPNG"))return;
				$im_in = ImageCreateFromPNG($fname);
				if(!$im_in){return -1;}
				break;
			default : return -2;
		}
	} catch (Exception $e)
	{
		return -1;
	}
	// Resizing
	if ($size[0] > $width || $size[1] >$height) {
		$key_w = $width / $size[0];
		$key_h = $height / $size[1];
		($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
		$out_w = ceil($size[0] * $keys) +1;
		$out_h = ceil($size[1] * $keys) +1;
	} else {
		$out_w = $size[0];
		$out_h = $size[1];
	}
	// the thumbnail is created
	if(function_exists("ImageCreateTrueColor")){
		$im_out = ImageCreateTrueColor($out_w, $out_h);
	}else{$im_out = ImageCreate($out_w, $out_h);}
	// copy resized original
	ImageCopyResized($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
	$rcolor = imagecolorallocate($im_out, 255, 255, 238);
	imagecolortransparent($im_out, $rcolor);
	// thumbnail saved
	
	ImageJPEG($im_out, $thumb_dir.$tim.'.jpg',60);
	chmod($thumb_dir.$tim.'.jpg',0666);
	// created image is destroyed
	ImageDestroy($im_in);
	ImageDestroy($im_out);
}

function isImage($path)
{
	$a = getimagesize($path);
	$image_type = $a[2];

	if(in_array($image_type , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG)))
	{
		return true;
	}
	return false;
}

function deletePost($postno, $password, $onlyimgdel = 0)
{
	global $db_username, $db_database, $db_host, $db_password, $db_prefix;
	$conn = mysqli_connect($db_host, $db_username, $db_password, $db_database);
	if (is_numeric($postno))
	{
		$result = mysqli_query($conn, "SELECT * FROM posts WHERE id=".$postno);
		if (mysqli_num_rows($result) == 1)
		{
			$postdata = mysqli_fetch_assoc($result);
			if (md5($password) == $postdata['password'])
			{
				if ($onlyimgdel == 1)
				{
					if (!empty($postdata['filename']))
					{
						mysqli_query($conn, "UPDATE posts SET filename='deleted' WHERE id=".$postno.";");
						if ($postdata['resto'] != 0)
						{
							generateView($postdata['resto']);
							generateView();
						} else {
							generateView($postno);
							generateView();
						}
						return 1; //done-image
					} else {
						return -3;
					}
				} else {
					if ($postdata['resto'] == 0) //we'll have to delete whole thread
					{
						mysqli_query($conn, "DELETE FROM posts WHERE resto=".$postno.";");
						mysqli_query($conn, "DELETE FROM posts WHERE id=".$postno.";");
						generateView($postno);
						generateView();
						return 2; //done post
					} else {
						mysqli_query($conn, "DELETE FROM posts WHERE id=".$postno.";");
						generateView($postdata['resto']);
						generateView();
						return 2;
					}
				}
			} else {
				return -1; //wrong password
			}
		} else {
			return -2;
		}
	} else {
		return -2;
	}
	mysqli_close();
}

function addPost($name, $email, $subject, $comment, $password, $filename, $orig_filename, $resto = null)
{

	global $db_username, $db_database, $db_host, $db_password, $db_prefix;
	$conn = mysqli_connect($db_host, $db_username, $db_password, $db_database);
	$resto = mysqli_real_escape_string($conn, $resto);
	if (($resto == 0) && (empty($filename)))
	{
		echo "<center><h1>Error: No file selected.</h1><br /><a href='./'>RETURN</a></center>";
		return;
	}
	
	if ((empty($filename)) && (empty($comment)))
	{
		echo "<center><h1>Error: No file selected.</h1><br /><a href='./'>RETURN</a></center>";
		return;
	}
	$lastbumped = time();
	mysqli_query($conn, "INSERT INTO ".$db_prefix."posts (date, name, email, subject, comment, password, orig_filename, filename, resto, ip, lastbumped, filehash)".
	"VALUES (".time().", '".$name."', '".$email."', '".$subject."', '".preprocessString($conn, $comment)."', '".md5($password)."', '".$orig_filename."', '".$filename."', ".$resto.", '".$_SERVER['REMOTE_ADDR']."', ".$lastbumped.", 'abc')");
	$id = mysqli_insert_id($conn);
	if ($resto != 0)
	{
		if (($email == "sage") || ($email == "nokosage") || ($email == "nonokosage"))
		{
		
		} else {
			mysqli_query($conn, "UPDATE ".$db_prefix."posts SET lastbumped=".time()." WHERE id=".$resto);
		}
	
	
	}
	if (($email == "nonoko") || ($email == "nonokosage"))
	{
		echo '<meta http-equiv="refresh" content="2;URL='."'./index.html'".'">';
		
	} else {
		if ($resto != 0)
		{
			echo '<meta http-equiv="refresh" content="2;URL='."'./res/".$resto.".html#p".$id."".'">';
		} else {
			echo '<meta http-equiv="refresh" content="2;URL='."'./res/".$id.".html'".'">';
			
		}
	}
	if ($resto == 0)
	{
		generateView($id);
	} else {
		generateView($resto);
	}
	generateView();
	mysqli_close($conn);
}
?>
<html>
<head>
<title>Updating index</title>
</head>
<body>
<center><h1>Updating Index...</h1></center>

<?php
if (isset($_POST['mode']))
{
	$mode = $_POST['mode'];
	switch($mode)
	{
		case "regist":
			$filename = null;
			if (!empty($_FILES['upfile']['name']))
			{
				$target_path = "src/";
				$fileid = time() . rand(10000000, 999999999);
				$ext = pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION);
				$filename = $fileid . "." . $ext; 
				$target_path .= $filename;
				if (!isImage($_FILES['upfile']['tmp_name']))
				{
					echo "<h1>File is not an image!</h1></body></html>";
					exit;
				}
				$file_size = $_FILES['upfile']['size'];
				if ($file_size > 2097152)
				{
					echo "<h1>File size too big!</h1></body></html>";
					exit;
				}

				if(move_uploaded_file($_FILES['upfile']['tmp_name'], $target_path)) {
					echo "The file ".basename( $_FILES['upfile']['name'])." has been uploaded";
				} else {
					echo "There was an error uploading the file, please try again!";
					$filename = "";
				}
			}

			$name = "Anonymous";
			if ($_POST['name'] != "") { $name = $_POST['name']; }
			$resto = 0;
			if (isset($_POST['resto'])) { $resto = $_POST['resto']; }
			$password = "";
			if (empty($_POST['pwd']))
			{
				if (isset($_COOKIE['password']))
				{
					$password = $_COOKIE['password'];
				} else {
					$password = randomPassword();
				}
			} else {
				$password = $_POST['pwd'];
			}
			if (!empty($_FILES['upfile']['name']))
			{
				if ($resto != 0)
				{
					if (thumb("./src/", $fileid, ".".$ext, 125) < 0)
					{
						echo "<h1>Could not create thumbnail!</h1></body></html>"; exit;
					}
				} else {
					if (thumb("./src/", $fileid, ".".$ext) < 0)
					{
						echo "<h1>Could not create thumbnail!</h1></body></html>"; exit;
					}
				}
			}
			setcookie("password", $password, time() + 86400*256);
			addPost($name, $_POST['email'], $_POST['sub'], $_POST['com'], $password, $filename, basename($_FILES['upfile']['name']), $resto);
			break;
		case "usrdel":
			$onlyimgdel = 0;
			$password = "";
			if (isset($_COOKIE['password'])) { $password = $_COOKIE['password']; }
			if ((isset($_POST['onlyimgdel']) && ($_POST['onlyimgdel'] == "on"))) { $onlyimgdel = 1; }
			if (!empty($_POST['pwd'])) { $password = $_POST['pwd']; }
			foreach ($_POST as $key => $value)
			{
				if ($value == "delete")
				{
					$done = deletePost($key, $password, $onlyimgdel);
					if ($done == -1) {
						echo "Bad password for post ".$key.".<br />";
					} elseif ($done == -2) {
						echo "Post ".$key." not found.<br />";
					} elseif ($done == -3) {
						echo "Post ".$key." has no image.<br />";
					} elseif ($done == 1) {
						echo "Deleted image from post ".$key.".<br />";
					} elseif ($done == 2) {
						echo "Deleted post ".$key.".<br />";
					}
				}
			}
			echo '<meta http-equiv="refresh" content="2;URL='."'./index.html'".'">';
			break;
	}
} else {
	generateView();
	updateThreads();
	//regenThumbnails();
	echo '<meta http-equiv="refresh" content="2;URL='."'./index.html'".'">';
}
?>
</body>
</html>
