<?
$urllist = __DIR__ . '/URLs.txt';
$urls = preg_split("/[\s,]+/", file_get_contents($urllist));
$missingchapter = '';
foreach ($urls as $url)
{
    $comic = getcomicname($url);
	
	if ($comic != '') 
	{
    echo $comic . ' ('.$url.')
';
	}
	else {
    echo 'no comic name returned for : '.$comic . ' ('.$url.')
';
		continue;
	}
    foreach (comic2chapterlist($url) as $chaptername => $chapterurl)
    {
        $pageiscomplete = true;
        $CBZpath = __DIR__ . '/CBZ/' . $comic . '/' . $comic . ' - ' . $chaptername . '.cbz';
        if (file_exists($CBZpath))
        {
            echo '   ' . end(explode('/', $CBZpath)) . ' is exist, skip;
';
            continue;
        }
        
        foreach (page2jpglink($chapterurl) as $jpglink_b4handle => $totalpage)
        {
            $jpglink = array_values(explode('replacepagenum', $jpglink_b4handle)) [0];
            $jpgtype = array_values(explode('replacepagenum', $jpglink_b4handle)) [1];
            if (!isset($jpglink))
            {
                echo '   ' . '   ' . 'This has no jpglink!!! STOP Execution!!!';
                continue;
            }
            else
            {
                echo '   ' . $chaptername . ' ('.$chapterurl.')
';
                echo '   ' . '   ' . 'Begin Download:' . $jpglink . '[1-' . $totalpage . ']' . $jpgtype . '
';
                if (!downloadimg($comic, $chaptername, $jpglink, $totalpage)) //download image
                {
                    echo '   ' . '   ' . 'some page is still not downloaded.
';
                    $missingchapter += $comic." - ". $chaptername . ' ('.$chapterurl.') - ['.$totalpage.' pages] \n\r';
                    continue;
                }
                else	//create CBZ
                {
                    if (!generateCBZ($comic, $chaptername, $jpglink, $totalpage))
                    {
                        echo '   ' . '   ' . 'failed in creating CBZ';
                    }
                }
            }
        }
    } 
    cleantempfolder($comic);
    downloadcoverpic($comic,$url);
    //exit; //only run for one comic
}
file_put_contents(__DIR__.'/missing_chapters.txt',$missingchapter);

//-----------------function list----------------//
function cleantempfolder($comic) 
{
	if (file_exists(__DIR__.'/temp/'.$comic)) {rmdir(__DIR__.'/temp/'.$comic);}
}
function downloadcoverpic($comic,$url) 
{	
	$coverurl = 'https://www.2animx.com/upload/icon/H/'.end(explode('-',$url)).'/icon.jpg';
	if(!file_exists(__DIR__.'/CBZ/'.$comic.'/cover.jpg') || filesize(__DIR__.'/CBZ/'.$comic.'/cover.jpg') < 20000){
		$start_memory_img = memory_get_usage();
		$downloadpage_img = fopen($coverurl, 'r');
		$downloadpagesize_img = memory_get_usage() - $start_memory_img;
		file_put_contents(__DIR__.'/CBZ/'.$comic.'/cover.jpg', $downloadpage_img);
	}
}
function getcomicname($url)
{
    $html = file_get_contents($url);

    $doc = new DOMDocument();
    @$doc->loadHTML($html);

    $tags = $doc->getElementsByTagName('title');
    foreach ($tags as $tag)
    {
        $comicname = $tag->nodeValue;
    }
    $comicname = explode('漫畫', $comicname);
	//$comicname = end(explode('name-', $url));
	//$comicname = explode('-id', $comicname);
	$comicname = $comicname[0];
    return $comicname;
}

function comic2chapterlist($url)
{
    $chapterlist = array();

    $html = file_get_contents($url);
	
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
	
    $tags = $doc->getElementsByTagName('a');
	
    foreach ($tags as $tag)
    {
        $chapterurl = $tag->getAttribute('href'); //echo $jpg;
        $chaptername = $tag->nodeValue;
        $chaptername = array_values(explode(' ', $chaptername)) [0];
        $chaptername = str_replace('第', '第 ', $chaptername);
        $chaptername = str_replace('回', ' 回', $chaptername);
        $chaptername = str_replace('卷', ' 卷', $chaptername);
        $chaptername = str_replace('話', ' 話', $chaptername);
        $chaptername = str_replace('章', ' 章', $chaptername);
        if (strpos($chaptername, ' ') != false)
        {
        	$chaptername = array_values(explode(' ', $chaptername)) [0] . ' ' . sprintf('%03d', array_values(explode(' ', $chaptername)) [1]) . ' ' . array_values(explode(' ', $chaptername)) [2];
        }
        else {
        	//not common chaptper name
        }
        if (!in_array($chaptername, array_keys($chapterlist)))
        {
            if (strpos($chapterurl, 'cid') != false)
            {

                // echo '     have cid - ';
                //if(strpos($chaptername,'第') != false){
                //     echo 'have th - ';
                $chapterlist[$chaptername] = $chapterurl;
                //    }
                
            }
        }
    }

    if (count($chapterlist) === 0)
    {
        echo '   ' . 'No chapter is found in:' . $url . '
   ' . 'Could be due to expicit (18+) content. Skip' . '
';
    }

    return $chapterlist;
}
function page2jpglink($url)
{
    $html = file_get_contents($url);

    $doc = new DOMDocument();
    @$doc->loadHTML($html);

    $allimglink = array();
    $isjpglink = false;
    $ispnglink = false;

    $tags = $doc->getElementsByTagName('img');
    foreach ($tags as $tag)
    {
        $jpg = $tag->getAttribute('src'); //echo $jpg;
        if (strpos($jpg, '1.jpg') != false || strpos($jpg, '2.jpg') != false)
        {
            $jpglinkend = end(explode('/', $jpg)); //1.jpg
            $jpglink = str_replace($jpglinkend, '', $jpg);
            //echo '   '.$jpglink.'';
            $isjpglink = true;
            break;
        }
        else if (strpos($jpg, '1.png') != false || strpos($jpg, '2.png') != false)
        {
            $jpglinkend = end(explode('/', $jpg)); //1.png
            $jpglink = str_replace($jpglinkend, '', $jpg);
            //echo '   '.$jpglink.'';
            $ispnglink = true;
            break;
        }
        else
        {
            continue;
        }
    }
    $totalpages = $doc->getElementsByTagName('h1');
    foreach ($totalpages as $totalpage)
    {
        $totalpage = $totalpage->nodeValue;
        $totalpage = end(explode('/', $totalpage));
        $totalpage = array_values(explode(' ', $totalpage)) [1];
        //echo $totalpage;
        
    }

    if ($isjpglink)
    {
        return array(
            $jpglink . 'replacepagenum' . '.jpg' => $totalpage
        );
    }
    else if ($ispnglink)
    {
        return array(
            $jpglink . 'replacepagenum' . '.png' => $totalpage
        );
    }
    else {return false;}

}

function downloadimg($comic, $chaptername, $jpglink, $totalpage)
{
    $pageiscomplete = true;
    for ($i = 1;$i <= $totalpage;$i++)
    {
        $structure = __DIR__ . '/temp/' . $comic . '/';
        if (!file_exists($structure))
        {
            mkdir($structure, 0777, true);
            echo '   ' . '   ' . '   ' . 'created folder:' . $structure . '
';
        }
        $filename = $structure . $comic . '-' . $chaptername . '-' . sprintf('%03d', $i) . '.jpg';
        if (is_file($filename) && filesize($filename) > 1000)
        {
            echo '   ' . '   ' . '   ' . 'image exist: ' . end(explode('/', $filename)) . ', skip;
';
        }
        else if ($jpglink == '')
        {
            $pageiscomplete = false;
            echo '   ' . '   ' . '   ' . 'jpglink not provided: ' . $jpglink . ', break;
';
            break;
        }
        else
        {
            //test png or jpg and download
            if (isset($jpgorpng) && ($fp = fopen($jpglink . $i . $jpgorpng, "rb")) !== false)
            {
                $str = stream_get_contents($fp);
                file_put_contents($filename, $str);
                fclose($fp);
                // send success JSON
                
            }
            else if (($fp = fopen($jpglink . $i . '.jpg', "rb")) !== false)
            {
                $jpgorpng = '.jpg';
                $str = stream_get_contents($fp);
                file_put_contents($filename, $str);
                fclose($fp);
                // send success JSON
            }
            else if (($fp = fopen($jpglink . $i . '.png', "rb")) !== false)
            {
                $jpgorpng = '.png';
                $str = stream_get_contents($fp);
                file_put_contents($filename, $str);
                fclose($fp);
                // send success JSON
            }
            else if ($i === $totalpage)
            {
                echo '   ' . '   ' . '   ' . 'Assume it is typo of total page, skip downloading final page' . '
';
            	//continue;
            }
            else 
            {
                echo '   ' . '   ' . '   ' . 'Cannot open link: ' . $jpglink . $i . '.jpg(.png)
';
                //break;
                // send error message if you can
                
            }
            
            //download the page
            //$downloadpage_img = fopen($downloadpage_imglink, 'r');
            //if(file_put_contents($filename, $downloadpage_img)) 
            //{
	            //check downloaded file
            if (is_file($filename) && filesize($filename) > 1000)
            {
                echo '   ' . '   ' . '   ' . 'downloaded image: ' . end(explode('/', $filename)) . ', next;
';
            }
            else
            {
                echo '   ' . '   ' . '   ' . 'download fail: ' . end(explode('/', $filename)) . ', file size (' . round(filesize($filename) / 1024) . 'KB)too small, break;
';
                echo '   ' . '   ' . '   ' . 'link: ' . $jpglink . $i . $jpgorpng . '
';
                $pageiscomplete = false;
            }
            //}
	        //else {
	        //	$pageiscomplete = false;
	        //}
        }
    }
    //rmdir($structure);
    return $pageiscomplete;
}

function generateCBZ($comic, $chaptername, $jpglink, $totalpage)
{
    $structure = __DIR__ . '/temp/' . $comic . '/';
    $CBZpath = __DIR__ . '/CBZ/' . $comic . '/' . $comic . ' - ' . $chaptername . '.cbz';
    //echo "This chapter is complete, generating a CBZ file for backup.<br>";
    if (!file_exists(__DIR__ . '/CBZ/' . $comic))
    {
        mkdir(__DIR__ . '/CBZ/' . $comic, 0777, true);
    }
    if (!file_exists($CBZpath))
    {
        $zip = new ZipArchive;
        $zip->open($CBZpath, ZipArchive::CREATE);
        for ($i = 1;$i <= $totalpage;$i++)
        {
            $filename = $structure . $comic . '-' . $chaptername . '-' . sprintf('%03d', $i) . '.jpg';
            $zip->addFile($filename, $comic . '-' . $chaptername . '-' . sprintf('%03d', $i) . '.jpg');
        }
        $zip->close();
        echo '   ' . '   ' . 'CBZ Created: ' . $comic . ' - ' . $chaptername . '.cbz' . ';
';
        for ($i = 1;$i <= $totalpage;$i++)
        {
            $filename = $structure . $comic . '-' . $chaptername . '-' . sprintf('%03d', $i) . '.jpg';
            if (file_exists($filename))
            {
                unlink($filename);
            }
        }
    }
    return true;
}

function generateComicInfo($comic, $chaptername, $summary, $totalpage)
{
    $structure = __DIR__ . '/temp/' . $comic . '/';
    $CBZpath = __DIR__ . '/CBZ/' . $comic . '/' . $comic . ' - ' . $chaptername . '.cbz';
    //echo "This chapter is complete, generating a CBZ file for backup.<br>";
    if (file_exists(__DIR__ . '/CBZ/' . $comic))
    {
        if (!file_exists($CBZpath))
        {
            $zip = new ZipArchive;
            $zip->open($CBZpath, ZipArchive::OPEN);
            $xml = '<?xml version="1.0"?>
<ComicInfo xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xmlns:xsd="http://www.w3.org/2001/XMLSchema">
 <Title>' . $comic . '</Title>
 <Summary>' . $summary . '</Summary>
 <Number>' . $chaptername . '</Number>
 <Year>' . $year . '</Year>
 <Month>' . $month . '</Month>
 <Day>' . $day . '</Day>
 <Writer>' . $writer . '</Writer>
 <Genre>' . $Genre . '</Genre>
 <Manga>Yes</Manga>
 <PageCount>' . $totalpage . '</PageCount>
</ComicInfo>';
            $zip->addFile('ComicInfo.xml', $xml);
            $zip->close();
            echo '   ' . '   ' . '   ' . 'ComicInfo.xml has been added: ' . $comic . ' - ' . $chaptername . '.cbz' . ';
';
        }
        return true;
    }
    else
    {
        return false;
    }
}

?>
