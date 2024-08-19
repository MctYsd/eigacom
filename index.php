<?php
/**
 * 映画.com内の見たリストを抽出
 * filmarksにも登録出来るようにページのリンクを列挙。
 * 
 * 登録数が多すぎる場合は途中で蹴られる可能性あり。
 * ４～５００件くらいならまだ余裕ありそう。
 * 
 */


$initialUrl = "https://eiga.com/user/920937/movie/";// 取得したい映画COMのURL　数字はユーザーID
$filmarkSearchPage="https://filmarks.com/search/movies?q=";
$favList="fav.txt";//textファイルにも保存

function fetchPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36");
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0); 
    $html = curl_exec($ch);
    
    return $html;
}

if(file_exists($favList)){
    unlink($favList);
}

function parsePage($html) {
    global $favList,$filmarkSearchPage;
    $dom = new DOMDocument();
    if(!empty($html)){
        @$dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        //この辺りは変更される場合もあるので臨機応変に
        $linkNodes = $xpath->query("//a[@class='next icon-after']");
        $elements = $xpath->query("//h3[@class='title']");

        foreach ($elements as $element) {
            $favfilm=$element->nodeValue;
            file_put_contents($favList,$favfilm. PHP_EOL, FILE_APPEND);
            echo '<a href="'.$filmarkSearchPage.urlencode($favfilm).'" target="_blank">'.$favfilm."</a><br>";
        }

        if ($linkNodes->length > 0) {
            $nextPageUrl = $linkNodes->item(0)->getAttribute('href');
            return $nextPageUrl;
        } else {
            return null;
        }

    }else{
        die("empty...");
    }

}

header("Content-type: text/html; charset=UTF-8"); 

// ページを順に取得
do {
    $html = fetchPage($initialUrl);
    $nextPageUrl = parsePage($html);

    if ($nextPageUrl) {
        // 相対URLを絶対URLに変換
        $parsedUrl = parse_url($initialUrl);
        $initialUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $nextPageUrl;

    } else {
        echo "<hr>TOTAL:".count(file($favList))." favs \n";
    }

} while ($nextPageUrl);



?>