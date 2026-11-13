<?php
/**
 * Textos legales cortos, "versión inicial" (sección 4 del brief).
 * BORRADOR SIN REVISIÓN LEGAL: un abogado debe revisarlos antes del primer pedido real.
 * Los datos del negocio salen de config.php; si faltan se muestran como "[por completar]".
 *
 * Bloques: ['p' => texto] párrafo, ['ul' => [...]] lista, ['ol' => [...]] pasos en orden,
 * ['dl' => [término => detalle]] datos. Todo se escapa al imprimir.
 */

declare(strict_types=1);

function policy_pages(): array
{
    $business = (string) config('business_name');
    $legalName = config_text('business_legal_name');
    $nit = config_text('business_nit');
    $email = config_text('business_email');
    $whatsapp = (string) config('whatsapp_display');
    $freeShipping = format_price((int) config('free_shipping_min'));
    $shippingCost = format_price((int) config('shipping_cost'));
    $deliveryTime = delivery_label();
    $deviceTerm = warranty_label((int) config('warranty_months', 12));
    $accessoryTerm = warranty_label((int) config('warranty_accessory_months', 3));
    $warrantySummary = warranty_summary();
    $exchangeDays = config_text('warranty_exchange_days');
    $shippingPayer = config_text('warranty_shipping_payer');
    $retractoDays = (int) config('retracto_days', 5);
    $retractoRefundDays = (int) config('retracto_refund_days', 30);

    $seller = ['dl' => [
        'Razón social' => $legalName,
        'NIT' => $nit,
        'Correo' => $email,
        'WhatsApp' => $whatsapp,
    ]];

    return [
        'privacidad' => [
            'title' => 'Política de tratamiento de datos personales',
            'intro' => "En {$business} cuidamos los datos que nos compartes. Aquí explicamos qué datos recogemos, para qué los usamos y cómo ejerces tus derechos, según la Ley 1581 de 2012.",
            'sections' => [
                ['id' => 'responsable', 'heading' => 'Responsable del tratamiento', 'blocks' => [
                    $seller,
                    ['p' => 'Somos una tienda 100 % en línea, con envíos a toda Colombia.'],
                ]],
                ['id' => 'datos', 'heading' => 'Qué datos recogemos', 'blocks' => [
                    ['ul' => [
                        'Cuando nos escribes por WhatsApp: tu nombre, tu número de teléfono y lo que nos cuentas en la conversación.',
                        'Si pagas en línea en este sitio: nombre, cédula, celular, correo, departamento, ciudad, dirección y barrio de entrega, que escribes en el formulario de envío. Los usamos solo para despachar y facturar tu pedido.',
                        'Si compras: documento de identidad o NIT, correo, ciudad y dirección de entrega, y los datos que se necesiten para la factura y el envío.',
                        'En este sitio, solo si aceptas las cookies de analítica: datos de navegación, como páginas y productos vistos y clics en los botones de WhatsApp. Nunca tu nombre, teléfono ni correo.',
                        'No pedimos ni guardamos datos de tarjetas: los pagos con tarjeta se hacen en la plataforma de la pasarela de pago.',
                    ]],
                ]],
                ['id' => 'finalidades', 'heading' => 'Para qué los usamos', 'blocks' => [
                    ['ul' => [
                        'Responder tus preguntas y confirmar la disponibilidad de los productos.',
                        'Gestionar tu compra: pago, envío y seguimiento del pedido.',
                        'Atender garantías, cambios y reclamos.',
                        'Si lo aceptas, medir el uso del sitio para mejorarlo (Google Analytics y Meta Pixel).',
                    ]],
                ]],
                ['id' => 'autorizacion', 'heading' => 'Tu autorización', 'blocks' => [
                    ['p' => 'Antes de pedirte datos personales por WhatsApp, el asesor te pedirá tu autorización para tratarlos según esta política. Puedes revocarla cuando quieras, salvo que la ley o un contrato vigente nos obliguen a conservarlos.'],
                    ['p' => 'Si compras en línea, nos das tu autorización al marcar la casilla del formulario de envío.'],
                ]],
                ['id' => 'terceros', 'heading' => 'Con quién los compartimos', 'blocks' => [
                    ['p' => 'Solo con quienes nos ayudan a completar tu compra: la empresa transportadora y la pasarela de pago, cuando apliquen. WhatsApp (de Meta) es nuestro canal de atención. Si aceptas las cookies de analítica, Google y Meta reciben datos de navegación. No vendemos tus datos.'],
                ]],
                ['id' => 'derechos', 'heading' => 'Tus derechos', 'blocks' => [
                    ['ul' => [
                        'Conocer, actualizar y rectificar tus datos.',
                        'Pedir prueba de la autorización que nos diste.',
                        'Saber para qué hemos usado tus datos.',
                        'Revocar la autorización o pedir que eliminemos tus datos, cuando no haya un deber legal de conservarlos.',
                        'Acceder gratis a tus datos.',
                        'Presentar quejas ante la Superintendencia de Industria y Comercio (SIC).',
                    ]],
                ]],
                ['id' => 'reclamos', 'heading' => 'Cómo ejercerlos', 'blocks' => [
                    ['p' => "Escríbenos por WhatsApp al {$whatsapp} o al correo {$email} con tu nombre, un número de contacto y tu solicitud. Respondemos dentro de los plazos que fija la Ley 1581 de 2012."],
                ]],
                ['id' => 'cookies', 'heading' => 'Cookies', 'blocks' => [
                    ['p' => 'Usamos una cookie propia para recordar tu decisión sobre las cookies de analítica. Google Analytics y Meta Pixel solo se activan si las aceptas, y puedes cambiar tu decisión cuando quieras desde el enlace «Cookies» al pie de la página.'],
                    ['p' => 'La presentación inicial del sitio usa el almacenamiento de sesión del navegador para no repetirse. No es una cookie y se borra al cerrar el navegador.'],
                ]],
                ['id' => 'conservacion', 'heading' => 'Conservación', 'blocks' => [
                    ['p' => 'Guardamos tus datos solo el tiempo necesario para las finalidades de esta política y para cumplir nuestras obligaciones legales.'],
                ]],
            ],
        ],

        'terminos' => [
            'title' => 'Términos y condiciones',
            'intro' => "Estos términos aplican a las compras que haces en {$business} a través de este sitio y de WhatsApp. Al confirmar una compra, los aceptas.",
            'sections' => [
                ['id' => 'vendedor', 'heading' => 'Quién vende', 'blocks' => [
                    $seller,
                    ['p' => 'Somos una tienda 100 % en línea: no tenemos local físico.'],
                ]],
                ['id' => 'precios', 'heading' => 'Precios', 'blocks' => [
                    ['ul' => [
                        'Los precios están en pesos colombianos (COP) e incluyen IVA.',
                        'Cada producto tiene dos precios según cómo pagues: uno por consignación, depósito en efectivo o transferencia, y otro con tarjeta de crédito o débito, PSE o Nequi a través de la pasarela de pago.',
                        'El precio con pasarela incluye el costo de la plataforma de pago. Ambos son precios finales, sin cargos ocultos.',
                        'El precio y la disponibilidad se confirman por WhatsApp antes del pago.',
                    ]],
                ]],
                ['id' => 'compra', 'heading' => 'Cómo comprar', 'blocks' => [
                    ['ol' => [
                        'Elige el producto, el color y la capacidad, y toca «Confirmar disponibilidad».',
                        'Un asesor te confirma por WhatsApp la disponibilidad y el valor total.',
                        'Pagas con el medio que elijas. Si es con pasarela, te enviamos el enlace de pago por WhatsApp.',
                        'Cuando el pago está confirmado en nuestra cuenta, despachamos el pedido y te compartimos el seguimiento por WhatsApp.',
                    ]],
                ]],
                ['id' => 'pagos', 'heading' => 'Medios de pago', 'blocks' => [
                    ['ul' => [
                        'Consignación o depósito en efectivo y transferencia bancaria.',
                        'Tarjeta de crédito o débito, PSE y Nequi, a través de la pasarela de pago.',
                        'No recibimos pago contra entrega: todo pedido se paga antes del envío.',
                    ]],
                ]],
                ['id' => 'envios', 'heading' => 'Envíos', 'blocks' => [
                    ['p' => "Enviamos a toda Colombia. El envío es gratis en compras desde {$freeShipping}, según el precio que pagas con el medio elegido. Por debajo de ese valor, el envío cuesta {$shippingCost}. La entrega toma {$deliveryTime} después de confirmado el pago."],
                ]],
                ['id' => 'garantia', 'heading' => 'Garantía', 'blocks' => [
                    ['p' => "Todos los productos tienen garantía directa con {$business}: {$warrantySummary}. Si presenta un fallo, eliges cambio o reembolso. Las condiciones están en la página Garantía, cambios, reembolsos y envíos."],
                ]],
                ['id' => 'retracto', 'heading' => 'Derecho de retracto', 'blocks' => [
                    ['p' => 'Como la compra se hace a distancia, tienes derecho de retracto según el artículo 47 de la Ley 1480 de 2011 (Estatuto del Consumidor): puedes devolver el producto aunque no tenga ninguna falla.'],
                    ['ul' => [
                        "Plazo: {$retractoDays} días hábiles contados desde la entrega del producto.",
                        "Para ejercerlo, escríbenos por WhatsApp al {$whatsapp} dentro de ese plazo con tu nombre y el número de tu pedido.",
                        'Devuelves el producto por los mismos medios y en las mismas condiciones en que lo recibiste: sin uso, completo y con su empaque y accesorios.',
                        'El costo del envío de la devolución lo pagas tú, como lo establece la ley.',
                        "Te devolvemos todo el dinero que pagaste, sin descuentos ni retenciones, dentro de los {$retractoRefundDays} días calendario siguientes a la fecha en que ejerciste el derecho, por el mismo medio con el que pagaste.",
                    ]],
                ]],
                ['id' => 'ley', 'heading' => 'Ley aplicable', 'blocks' => [
                    ['p' => 'Estos términos se rigen por las leyes de Colombia. Si tienes un reclamo, escríbenos primero por WhatsApp; también puedes acudir a la Superintendencia de Industria y Comercio (SIC).'],
                ]],
            ],
        ],

        'garantia' => [
            'title' => 'Garantía, cambios, reembolsos y envíos',
            'intro' => "Todos nuestros productos tienen garantía directa con {$business}: la atendemos nosotros, sin intermediarios.",
            'sections' => [
                ['id' => 'taptop', 'heading' => "Garantía {$business}", 'blocks' => [
                    ['dl' => [
                        'Celulares, computadores, iPad y relojes' => $deviceTerm,
                        'Accesorios (AirPods, cargadores, adaptadores y Apple Pencil)' => $accessoryTerm,
                    ]],
                    ['ul' => [
                        'El plazo cuenta desde la entrega del producto.',
                        'La cubre y la atiende directamente ' . $business . ', no la marca: no tienes que acudir a Apple, a Samsung ni a un servicio técnico externo.',
                        'Cubre fallas de funcionamiento y defectos de fábrica del equipo o del accesorio, incluidas las fallas de la batería.',
                        'Si confirmamos la falla, tú eliges: cambio del producto o reembolso de tu dinero.',
                    ]],
                ]],
                ['id' => 'cambio', 'heading' => 'Si eliges cambio', 'blocks' => [
                    ['ul' => [
                        'Te cambiamos el producto por uno igual. Si no tenemos una unidad igual, lo cambiamos por un equipo equivalente del mismo valor.',
                        'Por equivalente entendemos un producto de la misma categoría, del mismo valor y con las mismas características. Te lo mostramos antes del cambio.',
                        "Hacemos el cambio en un plazo máximo de {$exchangeDays} días hábiles después de confirmar la falla.",
                    ]],
                ]],
                ['id' => 'reembolso', 'heading' => 'Si eliges reembolso', 'blocks' => [
                    ['ul' => [
                        'Te devolvemos el 100 % del valor que pagaste por el producto.',
                        'El dinero vuelve por el mismo medio con el que pagaste: a tu cuenta si pagaste por consignación o transferencia, o a través de la pasarela si pagaste con tarjeta, PSE o Nequi.',
                        "Hacemos el reembolso en un plazo máximo de {$exchangeDays} días hábiles después de confirmar la falla y recibir el producto. Si pagaste con tarjeta, tu banco puede tardar unos días más en reflejarlo.",
                    ]],
                ]],
                ['id' => 'exclusiones', 'heading' => 'Qué no cubre', 'blocks' => [
                    ['ul' => [
                        'Golpes, caídas, pantallas o vidrios rotos.',
                        'Humedad o contacto con líquidos.',
                        'Mal uso, reparaciones o manipulación por terceros.',
                        'Desgaste normal por el uso, incluida la pérdida normal de capacidad de la batería con el tiempo.',
                    ]],
                ]],
                ['id' => 'reclamo', 'heading' => 'Cómo hacer un reclamo', 'blocks' => [
                    ['ol' => [
                        "Escríbenos por WhatsApp al {$whatsapp} con tu nombre, el producto y tu factura {$business} o el número de tu pedido.",
                        'Envíanos fotos o un video donde se vea la falla.',
                        'Revisamos el caso y coordinamos el envío del equipo.',
                        'Si confirmamos la falla, eliges cambio o reembolso y lo hacemos en el plazo indicado.',
                    ]],
                    ['p' => "Para atender la garantía necesitamos la factura {$business} o el número de tu pedido. Envío del cambio o de la devolución: {$shippingPayer}."],
                ]],
                ['id' => 'envios', 'heading' => 'Envíos', 'blocks' => [
                    ['ul' => [
                        'Enviamos a toda Colombia.',
                        "Envío gratis en compras desde {$freeShipping}, según el precio que pagas con el medio elegido.",
                        "Por debajo de ese valor, el envío cuesta {$shippingCost}. Si pagas en línea, se suma al total.",
                        "Tiempo de entrega: {$deliveryTime} después de confirmado el pago.",
                        'El pedido sale cuando el pago está confirmado, y te compartimos el seguimiento por WhatsApp.',
                    ]],
                ]],
                ['id' => 'retracto', 'heading' => 'Derecho de retracto', 'blocks' => [
                    ['p' => "Si compraste y cambiaste de opinión, puedes devolver el producto dentro de los {$retractoDays} días hábiles siguientes a la entrega (artículo 47 de la Ley 1480 de 2011). Debe estar sin uso, completo y con su empaque. El envío de la devolución lo pagas tú, y te devolvemos todo el dinero dentro de los {$retractoRefundDays} días calendario siguientes. El detalle está en Términos y condiciones."],
                ]],
            ],
        ],
    ];
}
