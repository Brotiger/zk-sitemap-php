<?php

    set_time_limit(0);

    class SiteMap
    {
        private $host; 
        private $cType;
        private $page;
        private $siteMapPath;
        private $reg = [];
        private $siteMapIndex = 0;

        function __construct($url, $path){

            preg_match("~^(https?://)(.*)~", $url, $tmp);

            $this->cType = $tmp[1];
            $this->host = $tmp[2];
            $this->page = $url;
            $this->siteMapPath = $path . "/";

            $this->Generate();

            print("Sitemap created\n");
        }

        private function Generate()
        {

            $page = $this->page;

            $content = file_get_contents($page);

            if(!$content){
                return;
            }

            preg_match_all("~<[Aa][\s]{1}[^>]*[Hh][Rr][Ee][Ff][^=]*=[ '\"\s]*([^ \"'>\s#]+)[^>]*>~", $content, $tmp_home);
            
            foreach($tmp_home[0] as $key => $value){
                
                if(!preg_match('~<.*[Rr][Ee][Ll]=.?("|\'|).*[Nn][Oo][Ff][Oo][Ll][Ll][Oo][Ww].*?("|\'|).*~', $value)){
                    $links[] = $tmp_home[1][$key];
                }
            }

            foreach($links as $key => $value){
                if(!strstr($value, $this->cType)){
                    $links[$key] = $this->cType.$this->host.$value;
                }
                $url_info = parse_url($links[$key]);

                if($url_info['host'] != $this->host || strstr($links[$key], "@")){
                    continue;
                }

                $links[$key] = rtrim($links[$key], "/");
                $links[$key] = preg_replace("~/#.*~", '', $links[$key]);
                $urls[] = $links[$key];
            }

            $urls = array_unique($urls);

            #Формирование масива с регионами
            foreach($urls as $key => $value){
                preg_match("~".$this->cType.$this->host."/regions/([\S]*)$~", $value, $tmp_reg);
                if($tmp_reg != NULL){
                    $this->reg[] = $tmp_reg[1];
                }
            }

            #Формируем и записываем sitemap для каждого региона
            foreach($this->reg as $r_key => $r_value){

                $linksArrayObject = new ArrayObject($urls);
                $reg_urls = $linksArrayObject->getArrayCopy();

                foreach($reg_urls as $u_key => $u_value){
                    $reg_urls[$u_key] = preg_replace("~(".$this->cType.$this->host.")(/catalog/.*)~", "$1"."/regions/".$r_value."$2", $u_value);
                }
                $this->createSiteMap($reg_urls, 0.9);

            }

            #Записываем siteMap для главной страницы
            $this->createSiteMap($links, 1);

            #Генерация главного siteMap в который вложены остальные
            $this->createMainSiteMap();
        }

        #Запись корневого siteMap
        private function createMainSiteMap(){
            $date = date("Y-m-d\TH:i:sP");

            $sitemapXML = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            $index = $this->siteMapIndex - 1;

            while($index > 0){
                $location = $this->page . "/sitemap" .$index.".xml";
                $sitemapXML .= "\r\n<sitemap>\r\n\t<loc>{$location}</loc>\r\n\t<lastmod>{$date}</lastmod>\r\n</sitemap>";
                $index--;
            }

            $sitemapXML .= "\r\n</sitemapindex>";

            $this->writeFile($sitemapXML, "sitemap");
        }

        #Запись дочерних siteMap
        private function createSiteMap($links, $priority){

            $sitemapXML = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

            foreach($links as $key => $value){
                $date = date("Y-m-d\TH:i:sP");
                $sitemapXML.="\r\n<url>\r\n\t<loc>{$value}</loc>\r\n\t<lastmod>{$date}</lastmod>\r\n\t<changefreq>hourly</changefreq>\r\n\t<priority>{$priority}</priority>\r\n</url>";
            }

            $sitemapXML.="\r\n</urlset>";

            $this->writeFile($sitemapXML, "sitemap".$this->siteMapIndex);

            $this->siteMapIndex++;
            unset($sitemapXMLp);
        }

        private function writeFile($file, $name){
            $fp=fopen($this->siteMapPath.$name.'.xml','w+');

            if(!fwrite($fp,$file)){
                print('Write error');
            }

            fclose($fp);
        }
    }

    $SiteMap = new SiteMap("https://shop.zolotoykod.ru","./files");
?>