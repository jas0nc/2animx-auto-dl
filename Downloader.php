<?
$urllist = __DIR__.'/URLs.txt';
$urls = preg_split("/[\s,]+/", file_get_contents($urllist));
foreach ($urls as $url){
       $comic = getcomicname($url);
       echo $comic.'
';
       foreach (comic2chapterlist($url) as $chaptername => $chapterurl){
/*              echo '   '.$chaptername.'
';
              echo '   '.$chapterurl.'
';*/
              $pageiscomplete = true;
              //continue;
              $CBZpath = __DIR__.'/CBZ/'.$comic.'/'.$comic.' - '.$chaptername.'.cbz';
              if(file_exists($CBZpath)){
                            echo '   '.end(explode('/',$CBZpath)).' is exist, skip;
';
                     continue;
                     }
              foreach (page2jpglink($chapterurl) as $jpglink => $totalpage){
                     if (!isset($jpglink)){
                            echo '   '.'   '.'This has no jpglink!!! STOP Execution!!!';
                            continue;
                            }	
                     else {
                            echo '   '.'   '.'Begin Download:'.$jpglink.'[1-'.$totalpage.'].jpg
';					
                            //echo $totalpage.'<br>';
                            //create CBZ
                            if(!downloadimg($comic,$chaptername,$jpglink,$totalpage)) {
                                   echo '   '.'   '.'some page is still not downloaded.
';
                            continue;
                            }
                            else {
                                   if(!generateCBZ($comic,$chaptername,$jpglink,$totalpage)){ echo '   '.'   '.'failed in creating CBZ';}
                            }
                     }
              }
       }
}
       
function getcomicname($url){
       $html = file_get_contents($url);
       
       $doc = new DOMDocument();
       @$doc->loadHTML($html);
       
       $tags = $doc->getElementsByTagName('title');
       foreach ($tags as $tag) {$comicname = $tag->nodeValue;}
       $comicname = explode('漫畫',$comicname);
       return $comicname[0];
}

function comic2chapterlist($url){
       $chapterlist = array();
       
       $html = file_get_contents($url);
       
       $doc = new DOMDocument();
       @$doc->loadHTML($html);
       
       $tags = $doc->getElementsByTagName('a');
       
       foreach ($tags as $tag) {
              $chapterurl = $tag->getAttribute('href');//echo $jpg;
              $chaptername = $tag->nodeValue;
              $chaptername = array_values(explode(' ',$chaptername))[0];
              $chaptername = str_replace ('第', '第 ',$chaptername);
              $chaptername = str_replace ('回', ' 回',$chaptername);
              $chaptername = str_replace ('卷', ' 卷',$chaptername);
              $chaptername = str_replace ('話', ' 話',$chaptername);
              $chaptername = str_replace ('章', ' 章',$chaptername);
              $chaptername = array_values(explode(' ',$chaptername))[0].' '.sprintf('%03d', array_values(explode(' ',$chaptername))[1]).' '.array_values(explode(' ',$chaptername))[2];
              //$chapternamelist = array_keys($chapterlist);
              if(strpos($chapterurl,'cid') != false && !in_array($chaptername,array_keys($chapterlist))){
                   $chapterlist[$chaptername] = $chapterurl;
                   //echo $chaptername.$jpg.'<br>';
                   //echo count($chapterlist).$chaptername.$chapterurl.'<br>';
              }
       }
       return $chapterlist;
}
function page2jpglink($url){       
       $html = file_get_contents($url);
       
       $doc = new DOMDocument();
       @$doc->loadHTML($html);
       
       $tags = $doc->getElementsByTagName('img');
       foreach ($tags as $tag) {
              $jpg = $tag->getAttribute('src');//echo $jpg;
              if(strpos($jpg,'1.jpg') != false){
                   $jpglinkend = end(explode('/',$jpg));
                   $jpglink = str_replace($jpglinkend,'',$jpg);
                   //echo '   '.$jpglink.'';
              }
       }
       $totalpages= $doc->getElementsByTagName('h1');
       foreach ($totalpages as $totalpage) {
              $totalpage = $totalpage->nodeValue;
              $totalpage = end(explode('/',$totalpage));
              $totalpage = array_values(explode(' ',$totalpage))[1];
              //echo $totalpage;
       }
       //if (!isset($jpglink)){ print_r($tags);echo 'This has no jpglink!!! STOP Execution!!!';exit;}
       return array($jpglink => $totalpage);
}

function downloadimg($comic,$chaptername,$jpglink,$totalpage){
       $pageiscomplete = true;
       for ($i = 1; $i <= $totalpage; $i++) {
              $structure = __DIR__.'/temp/'.$comic.'/';
              if (!file_exists($structure)) {
                     mkdir($structure, 0777, true);
                     echo '   '.'   '.'   '.'created folder:'.$structure.'
';
              }
              $filename = $structure.$comic.'-'.$chaptername.'-'.sprintf('%03d', $i).'.jpg';
              if (is_file($filename) && filesize($filename) > 1000){
                     echo '   '.'   '.'   '.'image exist: '.end(explode('/',$filename)).', skip;
';
                     }
              else {
                     //test if secretkey is usable
                     $start_memory_img = memory_get_usage();
                     $downloadpage_img = fopen($jpglink.$i.'.jpg', 'r'); 
                     $downloadpagesize_img = memory_get_usage() - $start_memory_img;
                     file_put_contents($filename, $downloadpage_img);
                     if (is_file($filename) && filesize($filename) > 1000)
                     {
                            echo '   '.'   '.'   '.'downloading image: '.end(explode('/',$filename)).', next;
';							//echo '<img id="the_pic" class="center fit" src="/temp/'.$comic.'/'.$comic.'-'.$newcahptersname[$keys[$chap]].'-'.$i.'.jpg"><br>';
                     }
                     /*else if (is_file($filename) && file_get_contents($filename) != file_get_contents(__DIR__.'/temp/404.jpg') && filesize($filename) > 1000){
                            echo '   '.'   '.'downloading image: '.end(explode('/',$filename)).'(small but not 404), next;
';							//echo '<img id="the_pic" class="center fit" src="/temp/'.$comic.'/'.$comic.'-'.$newcahptersname[$keys[$chap]].'-'.$i.'.jpg"><br>';
                     }
                     else if (file_get_contents($filename) == file_get_contents(__DIR__.'/temp/404.jpg')){
                            echo '   '.'   '.'download fail: '.end(explode('/',$filename)).', (404 error), break;
';
                            //echo 'download failed: <a href="'.$jpglink.$i.'.jpg" target="_blank">'.$comic.'-'.$newcahptersname[$keys[$chap]].'-'.$i.'.jpg</a>. ('.round(filesize($filename)/1024,0).'KB)<br>';
                            $pageiscomplete = false;
                            break;
                     }*/
                     else {
                            echo '   '.'   '.'   '.'download fail: '.end(explode('/',$filename)).', file size ('.round(filesize($filename)/1024).'KB)too small, break;
';
                            echo '   '.'   '.'   '.'link: '.$jpglink.$i.'.jpg
';
                            //echo 'download failed: <a href="'.$jpglink.$i.'.jpg" target="_blank">'.$comic.'-'.$newcahptersname[$keys[$chap]].'-'.$i.'.jpg</a>. ('.round(filesize($filename)/1024,0).'KB)<br>';
                            $pageiscomplete = false;
                            //break;
                     }
              }      
       }
       //rmdir($structure);
       return $pageiscomplete;
}

function generateCBZ($comic,$chaptername,$jpglink,$totalpage){
       $structure = __DIR__.'/temp/'.$comic.'/';
       $CBZpath = __DIR__.'/CBZ/'.$comic.'/'.$comic.' - '.$chaptername.'.cbz';
       //echo "This chapter is complete, generating a CBZ file for backup.<br>";
       if (!file_exists(__DIR__.'/CBZ/'.$comic)) {
              mkdir(__DIR__.'/CBZ/'.$comic, 0777, true);
       }
       if (!file_exists($CBZpath)) {
              $zip = new ZipArchive;
              $zip->open($CBZpath, ZipArchive::CREATE);
              for ($i = 1; $i <= $totalpage; $i++) {
                     $filename = $structure.$comic.'-'.$chaptername.'-'.sprintf('%03d', $i).'.jpg';
                     $zip->addFile($filename,$comic.'-'.$chaptername.'-'.sprintf('%03d', $i).'.jpg');
              }
       $zip->close();
       echo '   '.'   '.'CBZ Created: '.$comic.' - '.$chaptername.'.cbz'.';
';
       for ($i = 1; $i <= $totalpage; $i++) {
              $filename = $structure.$comic.'-'.$chaptername.'-'.sprintf('%03d', $i).'.jpg';
              if (file_exists($filename)){
                            unlink($filename);
                     }
              }
       }
       return true;
}

function generateComicInfo($comic,$chaptername,$summary,$totalpage){
       $structure = __DIR__.'/temp/'.$comic.'/';
       $CBZpath = __DIR__.'/CBZ/'.$comic.'/'.$comic.' - '.$chaptername.'.cbz';
       //echo "This chapter is complete, generating a CBZ file for backup.<br>";
       if (file_exists(__DIR__.'/CBZ/'.$comic)) {
              if (!file_exists($CBZpath)) {
                     $zip = new ZipArchive;
                     $zip->open($CBZpath, ZipArchive::OPEN);
                     $xml = '<?xml version="1.0"?>
<ComicInfo xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
           xmlns:xsd="http://www.w3.org/2001/XMLSchema">
 <Title>'.$comic.'</Title>
 <Summary>'.$summary.'</Summary>
 <Number>'.$chaptername.'</Number>
 <Year>'.$year.'</Year>
 <Month>'.$month.'</Month>
 <Day>'.$day.'</Day>
 <Writer>'.$writer.'</Writer>
 <Genre>'.$Genre.'</Genre>
 <Manga>Yes</Manga>
 <PageCount>'.$totalpage.'</PageCount>
</ComicInfo>';
                     $zip->addFile('ComicInfo.xml',$xml);
                     $zip->close();
                     echo '   '.'   '.'   '.'ComicInfo.xml has been added: '.$comic.' - '.$chaptername.'.cbz'.';
';
              }
       return true;
       }
       else {
              return false;
       }
} 

?>