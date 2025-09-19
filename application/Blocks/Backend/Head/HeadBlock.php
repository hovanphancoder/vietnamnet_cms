<?php

namespace App\Blocks\Backend\Head;

use System\Libraries\Render;
use System\Core\BaseBlock;

class HeadBlock extends BaseBlock
{
    public function __construct()
    {
        $this->setLabel('Backend\Head Block');
        $this->setName('Backend\Head');
        $this->setProps([
            'layout'      => 'default',  // Tên file layout: default.php
            'title'       => 'My Website Title',
            'description' => 'This is the meta description of my website.',
            'keywords'    => '',
            //'canonical'   => 'https://mywebsite.com',
            'meta'        => [
                'author'   => 'My Company',
                'viewport' => 'width=device-width, initial-scale=1'
            ]
        ]);

        // Render::asset('js', 'js/tailwindcss.js', ['area' => 'backend', 'location' => 'header']);
        // $jsInline = <<<'JSINLINE'
        // setTimeout(() => {
        //   const l = document.createElement("link");l.rel = "stylesheet";
        //   l.href = "/themes/cmsfullform/Backend/assets/css/new_style.css";document.head.appendChild(l);
        // }, 5);
        // JSINLINE;
        // Render::inline('js', $jsInline, ['area' => 'backend', 'location' => 'header']);
        // Render::asset('css', 'css/simplebar.min.css', ['area' => 'backend', 'location' => 'head']);
        // //Render::asset('css', 'css/new_style.css', ['area' => 'backend', 'location' => 'head']);
        // Render::asset('css', 'css/font-inter.css', ['area' => 'backend', 'location' => 'head']);
    }

    public function handleData()
    {
        // Sử dụng dữ liệu menu được truyền vào
        $data = $this->getProps();

        return $data;
    }
}
