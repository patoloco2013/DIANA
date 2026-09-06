<?php
declare(strict_types=1);

namespace Diana\Core;

/**
 * Catálogos fijos: entidades federativas y claves del SAT (CFDI 4.0)
 * usadas por los perfiles fiscales de los socios.
 */
final class Catalogos
{
    public const ESTADOS = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
        'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
        'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
        'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
    ];

    public const MESES = [
        1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
    ];

    public const TIPOS_SOCIO = [
        'normal'     => 'Socio normal',
        'estudiante' => 'Socio estudiante',
        'vitalicio'  => 'Socio vitalicio',
        'honorario'  => 'Socio honorario',
        'no_socio'   => 'No socio',
    ];

    public const GENEROS = [
        'sin_especificar' => 'Sin especificar',
        'femenino'        => 'Femenino',
        'masculino'       => 'Masculino',
        'otro'            => 'Otro',
    ];

    public const TIPOS_DOCUMENTO = [
        'acta_nacimiento' => 'Acta de nacimiento',
        'curp'            => 'CURP',
        'ine'             => 'Identificación oficial (INE)',
        'cedula'          => 'Cédula profesional',
        'titulo'          => 'Título profesional',
        'cv'              => 'Currículum vitae',
        'comprobante'     => 'Comprobante de domicilio',
        'constancia_sat'  => 'Constancia de situación fiscal',
        'fotografia'      => 'Fotografía',
        'otro'            => 'Otro',
    ];

    /** c_RegimenFiscal (SAT). */
    public const REGIMEN_FISCAL = [
        '601' => 'General de Ley Personas Morales',
        '603' => 'Personas Morales con Fines no Lucrativos',
        '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
        '606' => 'Arrendamiento',
        '607' => 'Régimen de Enajenación o Adquisición de Bienes',
        '608' => 'Demás ingresos',
        '610' => 'Residentes en el Extranjero sin Establecimiento Permanente en México',
        '611' => 'Ingresos por Dividendos (socios y accionistas)',
        '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
        '614' => 'Ingresos por intereses',
        '615' => 'Régimen de los ingresos por obtención de premios',
        '616' => 'Sin obligaciones fiscales',
        '620' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
        '621' => 'Incorporación Fiscal',
        '622' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
        '623' => 'Opcional para Grupos de Sociedades',
        '624' => 'Coordinados',
        '625' => 'Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
        '626' => 'Régimen Simplificado de Confianza',
    ];

    /** c_UsoCFDI (SAT). */
    public const USO_CFDI = [
        'G01' => 'Adquisición de mercancías',
        'G02' => 'Devoluciones, descuentos o bonificaciones',
        'G03' => 'Gastos en general',
        'I01' => 'Construcciones',
        'I02' => 'Mobiliario y equipo de oficina por inversiones',
        'I03' => 'Equipo de transporte',
        'I04' => 'Equipo de cómputo y accesorios',
        'I05' => 'Dados, troqueles, moldes, matrices y herramental',
        'I06' => 'Comunicaciones telefónicas',
        'I07' => 'Comunicaciones satelitales',
        'I08' => 'Otra maquinaria y equipo',
        'D01' => 'Honorarios médicos, dentales y gastos hospitalarios',
        'D02' => 'Gastos médicos por incapacidad o discapacidad',
        'D03' => 'Gastos funerales',
        'D04' => 'Donativos',
        'D05' => 'Intereses reales pagados por créditos hipotecarios (casa habitación)',
        'D06' => 'Aportaciones voluntarias al SAR',
        'D07' => 'Primas por seguros de gastos médicos',
        'D08' => 'Gastos de transportación escolar obligatoria',
        'D09' => 'Depósitos en cuentas para el ahorro, primas de planes de pensiones',
        'D10' => 'Pagos por servicios educativos (colegiaturas)',
        'S01' => 'Sin efectos fiscales',
        'CP01' => 'Pagos',
        'CN01' => 'Nómina',
    ];

    /** Valida la estructura de un RFC (persona física 13, moral 12). */
    public static function rfcValido(string $rfc): bool
    {
        return (bool) preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc);
    }
}
