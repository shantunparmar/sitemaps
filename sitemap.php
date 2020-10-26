<?php
$file = $_GET['web'];
$ishttps = $_GET['val'];

include ('/var/www/html/' . $file . '/wp-blog-header.php');
echo $wpdb->base_prefix;

if ($ishttps == 1)
{
    $server = 'https';
}
else
{
    $server = 'http';
}

$directory = '/var/www/html/' . $file;
$base_url = $server . "://www." . $file;

include ('http.php');
$dir = '/var/www/html';
header('Content-type: text/plain;charset=UTF-8');

function make_category($file, $server)
{

    $client = new SimpleHTTPClient();

    $response = $client->makeRequest($server . '://www.' . $file, 'GET');
    $content = preg_replace("/<img[^>]+\>/i", "(image) ", $response['body']);
    preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $content, $result);

    $category = [];
    $category_url = [];
    if (!empty($result))
    {
        foreach ($result['href'] as $url)
        {
            if (strpos($url, '/category/') !== false)
            {
                $pieces = explode('/category/', $url);
                $last_word = array_pop($pieces);
                if (!in_array(rtrim($last_word, "/") , $category))
                {
                    $category[] = rtrim($last_word, "/");
                    $category_url[] = $server . '://www.' . $file . '/category/' . $last_word;

                    // $xml .= sprintf($pagePatt,$category_url,$freqs[0], $lastmod,$priority[0]);
                    
                }
            }
        }
    }

    return $category_url;

}

// xml starter
$xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\r\n";
// pattern to use for each page
$pagePatt = "\t<url>\r\n\t\t<loc>%s</loc>\r\n\t\t<changefreq>%s</changefreq>\r\n\t<lastmod>%s</lastmod>\r\n\t\t<priority>%s</priority>\r\n\t</url>\r\n";
$priority = array(
    1.0,
    0.9,
    0.8,
    0.7,
    0.6
);
$lastmod = date('c', time());
// Change frequency options
$freqs = array(
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly'
);

$xml .= "<url>
			<loc>" . $server . "://www." . $file . "/</loc>
			<changefreq>daily</changefreq>
			<lastmod>" . $lastmod . "</lastmod>
			<priority>1.0</priority>
			</url>";
$xmlfile1 = $directory . '/sitemap.xml'; // obfuscated for public forum
// pattern to use for each page
// Change frequency options
$freqs = array(
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly'
);

$urls = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE post_type='post'");

$categories = make_category($file, $server);

foreach ($categories as $category)
{

    $xml .= sprintf($pagePatt, $category, $freqs[1], $lastmod, $priority[1]);

}
// output data of each row
foreach ($urls as $url)
{
    $freq = 1;
    // add current page to the sitemap
    $url = $url->guid;

    $xml .= sprintf($pagePatt, $url, $freqs[$freq], $lastmod, $priority[$freq]);

}

// xml closer
$xml .= '</urlset>';

$saveFile = file_put_contents($xmlfile1, $xml);

