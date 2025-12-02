<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

class URL
{
    public static string $thrower = 'https://wwwmat.sat.gob.mx/app/seg/faces/pages/lanzador.jsf'
    . '?url=/operacion/53027/genera-tu-constancia-de-situacion-fiscal'
    . '&tipoLogeo=c&target=principal&hostServer=https://wwwmat.sat.gob.mx';

    public static string $logoutSatellite = 'https://wwwmat.sat.gob.mx/cs/Satellite'
    . '?childpagename=Common/Logic/COMMON_Logout&packedargs=locale=1462228413195&pagename=TySWrapper';

    public static string $closeSession = 'https://wwwmat.sat.gob.mx/app/seg/cerrarSesion';

    public static string $logout = 'https://login.siat.sat.gob.mx/nidp/app/plogout';
    public static string $rfcampc = 'https://rfcampc.siat.sat.gob.mx';
    public static string $file = 'https://rfcampc.siat.sat.gob.mx/PTSC/IdcSiat/IdcGeneraConstancia.jsf';
    public static string $base = 'https://login.siat.sat.gob.mx';
}
