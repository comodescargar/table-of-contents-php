# table-of-contents-php
Pon una tabla de contenidos en cualquier html. Con esta clase podremos integrar una tabla de contenidos al estilo wikipedia en cualquier lugar. También Llamado TOC esta clase está preparada para generar el cuadrito , gracias a mi compañero Héctor de http://comodescargarfull.com  por echarme un cable para desarrollar esta clase y que quede todo al estilo de wordpress como en su web.

Su modo de uso es muy Facil tan solo debes de inicializar la clase con el contenido en html y te devolvera el contenido con la tabla de contenidos dentro.

//inicializamos la clase con el html
$toc = new Toc($html);

//recogemos el html con la tabla de contenidos embebida y preparada
$toc_content = $toc->content;

Ahora solo quedaría poner en vuestro style.css u hoja de estilos

#toc_container span.toc_toggle {
    font-weight: 400;
    font-size: 90%
}

#toc_container p.toc_title + ul.toc_list {
    margin-top: 1em
}

.toc_wrap_left {
    float: left;
    margin-right: 10px
}

.toc_wrap_right {
    float: right;
    margin-left: 10px
}

#toc_container a {
    text-decoration: none;
    text-shadow: none
}

#toc_container a:hover {
    text-decoration: underline
}

.toc_sitemap_posts_letter {
    font-size: 1.5em;
    font-style: italic
}
