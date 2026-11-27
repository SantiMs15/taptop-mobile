<?php
/**
 * Tips del Inicio (sección "Tips"). TEXTOS DE EJEMPLO: se cambian por el contenido real.
 * Mientras no haya artículos, cada tarjeta lleva a /tips. Cuando exista un artículo,
 * su 'href' pasa a /tips/<slug>.
 */

declare(strict_types=1);

function home_tips(): array
{
    return [
        [
            'title' => '¿Cuánta capacidad necesito?',
            'text' => 'Si guardas fotos y videos en el celular, 256 GB te dan margen por años. Con 128 GB conviene usar la nube.',
            'href' => '/tips',
        ],
        [
            'title' => 'Cuida la batería desde el primer día',
            'text' => 'Evita el calor fuerte y las cargas al 100 % toda la noche. Activa la carga optimizada en ajustes.',
            'href' => '/tips',
        ],
        [
            'title' => 'Protege la pantalla',
            'text' => 'Un vidrio templado y un estuche con borde elevado evitan la mayoría de los daños por caídas.',
            'href' => '/tips',
        ],
        [
            'title' => 'Pasa tus datos al nuevo celular',
            'text' => 'Haz una copia de seguridad antes de cambiar. Así pasas fotos, chats y apps en pocos minutos.',
            'href' => '/tips',
        ],
        [
            'title' => '¿Cómo saber si es original?',
            'text' => 'Revisa el número de serie en la página oficial de la marca y compra con factura y garantía.',
            'href' => '/tips',
        ],
        [
            'title' => 'iPhone o Samsung: cuál elegir',
            'text' => 'Piensa en los equipos que ya usas, como reloj, audífonos o computador, y en cuánto tiempo lo vas a tener.',
            'href' => '/tips',
        ],
    ];
}
