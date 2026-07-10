<?php
require_once __DIR__ . '/db.php';

$d = db();

$pages = [
    'index' => [
        ['hero_cita_1', 'text', 'Cita hero línea 1', '"Yo necesité una'],
        ['hero_cita_2', 'text', 'Cita hero línea 2', 'segunda'],
        ['hero_cita_3', 'text', 'Cita hero línea 3', 'oportunidad"'],
        ['hero_descripcion', 'textarea', 'Descripción principal', 'Somos una corporación que acompaña y capacita a mujeres privadas de libertad e inserta familiar, social y laboralmente a quienes han cumplido condena.'],
        ['stat_1_numero', 'text', 'Estadística 1 - número', '45%'],
        ['stat_1_texto', 'text', 'Estadística 1 - texto', 'de la población penal es acompañada y recibe capacitación cada año'],
        ['stat_2_numero', 'text', 'Estadística 2 - número', '+23'],
        ['stat_2_texto', 'text', 'Estadística 2 - texto', 'años trabajando con mujeres del CPF'],
        ['stat_3_numero', 'text', 'Estadística 3 - número', '+130'],
        ['stat_3_texto', 'text', 'Estadística 3 - texto', 'mujeres han sido contratadas en los últimos 6 años por empresas colaboradoras'],
        ['stat_4_numero', 'text', 'Estadística 4 - número', '+10'],
        ['stat_4_texto', 'text', 'Estadística 4 - texto', 'empresas entregan cupos laborales a mujeres del CPF'],
    ],
    'nosotros' => [
        ['historia_titulo', 'text', 'Título Historia', 'NUESTRA HISTORIA'],
        ['historia_texto', 'textarea', 'Texto Historia', 'Hace 20 años un grupo de mujeres nos acercamos a la realidad carcelaria femenina y nos sentimos interpeladas por las historias de marginación y violencia que encontramos.'],
        ['mision_titulo', 'text', 'Título Misión', 'MISIÓN'],
        ['mision_texto', 'textarea', 'Texto Misión', 'Acompañar, capacitar e insertar familiar, social y laboralmente a mujeres infractoras de ley tanto durante su reclusión en el Centro Penitenciario Femenino de Santiago, como una vez que recuperan su libertad.'],
        ['vision_titulo', 'text', 'Título Visión', 'VISIÓN'],
        ['vision_texto', 'textarea', 'Texto Visión', 'Revertir el círculo vicioso de la delincuencia y la marginación en las mujeres privadas de libertad de manera que puedan insertarse familiar, social y laboralmente.'],
        ['directorio_titulo', 'text', 'Título Directorio', 'DIRECTORIO'],
        ['consejo_titulo', 'text', 'Título Consejo', 'CONSEJO DIRECTIVO'],
        ['consejo_miembros', 'textarea', 'Miembros Consejo', "Sonia Larraín\nAlejandra Neumann\nMaría Elena Riesco\nJuan Pablo Simian"],
        ['comite_titulo', 'text', 'Título Comité', 'COMITÉ FINANCIERO'],
        ['comite_miembros', 'textarea', 'Miembros Comité', "Rafael Guilisasti Gana\nMatías Concha Berthet\nBenjamín Díaz Fernández\nJosé Luis Mojica Undurraga"],
        ['equipo_titulo', 'text', 'Título Equipo', 'EQUIPO DE TRABAJO'],
        ['transparencia_titulo', 'text', 'Título Transparencia', 'TRANSPARENCIA'],
    ],
    'quehacemos' => [
        ['intra_titulo', 'text', 'Programa Intra - Título', 'PROGRAMA INTRA PENITENCIARIO'],
        ['intra_subtitulo', 'text', 'Programa Intra - Subtítulo', 'LA CÁRCEL COMO OPORTUNIDAD'],
        ['intra_texto', 'textarea', 'Programa Intra - Descripción', 'Convertimos el tiempo de reclusión en espacio de formación y capacitación.'],
        ['intra_stat_1_numero', 'text', 'Intra Stat 1 - número', '20%'],
        ['intra_stat_1_texto', 'text', 'Intra Stat 1 - texto', 'de la población CPF se capacita en talleres de oficio certificado por SENCE cada año.'],
        ['intra_stat_2_numero', 'text', 'Intra Stat 2 - número', '50%'],
        ['intra_stat_2_texto', 'text', 'Intra Stat 2 - texto', 'de la población penal es acompañada y recibe capacitación cada año.'],
        ['libertad_titulo', 'text', 'Programa Libertad - Título', 'PROGRAMA ABRIENDO PUERTAS EN LIBERTAD'],
        ['libertad_subtitulo', 'text', 'Programa Libertad - Subtítulo', 'RECUPERAR LA DIGNIDAD Y FAMILIA'],
        ['libertad_texto', 'textarea', 'Programa Libertad - Descripción', 'Facilitamos la obtención de empleos dignos y apoyamos el micro emprendimiento para permitir la vinculación con las familias.'],
        ['libertad_stat_hijos', 'text', 'Libertad - Stat hijos', '3.5'],
        ['libertad_stat_hijos_texto', 'text', 'Libertad - Stat hijos texto', 'Hijos tiene cada mujer, en promedio.'],
    ],
    'hazteparte' => [
        ['testimonio_socio', 'textarea', 'Testimonio Hazte Socio', '"Mi experiencia como voluntaria en la corporación me convenció que la cárcel solo invisibiliza la delincuencia por un tiempo. Sin posibilidades de inserción ese problema no tiene solución."'],
        ['socio_titulo', 'text', 'Título Hazte Socio', 'HAZTE SOCIO'],
        ['socio_texto', 'textarea', 'Texto Hazte Socio', 'Tu aporte permitirá que más mujeres tengan una segunda oportunidad y se integren social, familiar y laboralmente.'],
        ['testimonio_voluntario', 'textarea', 'Testimonio Voluntario', '"Ahora entiendo. Colaborando con el trabajo de inserción de mujeres privadas de libertad contribuyo a la no reincidencia y a la disminución de la delincuencia en Chile."'],
        ['voluntario_titulo', 'text', 'Título Hazte Voluntario', 'HAZTE VOLUNTARIO'],
        ['voluntario_texto', 'textarea', 'Texto Hazte Voluntario', 'Te invitamos a ser parte de una comunidad de acompañamiento, capacitación a las mujeres privadas de libertad del Centro Penitenciario Femenino de Santiago.'],
    ],
    'otec' => [
        ['hero_titulo', 'text', 'Título principal', 'UNA SEGUNDA OPORTUNIDAD'],
        ['hero_subtitulo', 'text', 'Subtítulo', 'QUE TRANSFORMA VIDAS'],
        ['hero_texto', 'textarea', 'Texto hero', 'Somos un Organismo Técnico de Capacitación (OTEC) que impulsa a la reinserción de mujeres vulnerables con herramientas para su futuro'],
        ['stat_1_numero', 'text', 'Stat 1 - número', '+25 AÑOS'],
        ['stat_1_texto', 'text', 'Stat 1 - texto', 'Capacitando y acompañando a mujeres vulnerables y a sus familias.'],
        ['stat_2_numero', 'text', 'Stat 2 - número', '82%'],
        ['stat_2_texto', 'text', 'Stat 2 - texto', 'Son mujeres que hoy generan ingresos propios o retomaron sus estudios.'],
        ['stat_3_numero', 'text', 'Stat 3 - número', '+10 Empresas'],
        ['stat_3_texto', 'text', 'Stat 3 - texto', 'Entregan cupos laborales a mujeres del CPF'],
        ['stat_4_numero', 'text', 'Stat 4 - número', '+130 Mujeres'],
        ['stat_4_texto', 'text', 'Stat 4 - texto', 'Contratadas los últimos 6 años en empresas'],
        ['capacitaciones_titulo', 'text', 'Título capacitaciones', 'CONOCE NUESTRAS CAPACITACIONES'],
        ['capacitaciones_subtitulo', 'text', 'Subtítulo capacitaciones', 'COSTURA - CONSTRUCCIÓN - EMPRENDIMIENTO'],
    ],
    'contacto' => [
        ['titulo', 'text', 'Título', 'CONTÁCTANOS'],
        ['subtitulo', 'text', 'Subtítulo', 'Escríbenos y te responderemos a la brevedad'],
        ['mensaje_exito', 'text', 'Mensaje de éxito formulario', '¡Gracias por tu mensaje!'],
    ],
    'footer' => [
        ['newsletter_texto', 'text', 'Texto newsletter', 'Suscríbete a nuestra newsletter'],
        ['newsletter_boton', 'text', 'Botón newsletter', 'Unirse'],
        ['copyright', 'text', 'Copyright', '© 2025 Corporación Abriendo Puertas'],
    ],
];

$stmt = $d->prepare("INSERT OR IGNORE INTO page_content (page, field, value, field_type, label, sort_order) VALUES (?,?,?,?,?,?)");
$total = 0;

foreach ($pages as $page => $fields) {
    foreach ($fields as $order => $f) {
        $stmt->execute([$page, $f[0], $f[3], $f[1], $f[2], $order]);
        if ($stmt->rowCount()) $total++;
    }
}

echo "Initialized $total page content fields.\n";
$count = $d->query("SELECT COUNT(*) FROM page_content")->fetchColumn();
echo "Total fields in database: $count\n";
