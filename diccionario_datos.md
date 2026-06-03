<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="author" content="WB DataDic" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mydb - Diccionario de Datos</title>
    <style type="text/css">
    :root {
        --primary: #2C3E50;
        --secondary: #34495E;
        --accent: #3498DB;
        --excel-green: #27AE60;
        --import-blue: #2980B9;
        --bg-color: #F8F9FA;
        --border-color: #E9ECEF;
    }
    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background-color: var(--bg-color);
        color: #333;
        line-height: 1.6;
        margin: 0;
        padding: 40px 20px;
        max-width: 1300px;
        margin: auto;
    }
    #title-sect {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    #title-sect h1 {
        margin: 0 0 10px 0;
        font-size: 2.5em;
    }
    .proj-desc {
        font-size: 1.1em;
        opacity: 0.9;
    }
    .controls {
        text-align: right;
        margin-bottom: 20px;
    }
    .btn-excel, .btn-import {
        color: white;
        border: none;
        padding: 12px 24px;
        font-size: 1em;
        font-weight: bold;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.1s ease;
        margin-left: 10px;
    }
    .btn-excel {
        background-color: var(--excel-green);
        box-shadow: 0 2px 4px rgba(39, 174, 96, 0.3);
    }
    .btn-excel:hover { background-color: #219653; transform: translateY(-2px); }
    .btn-import {
        background-color: var(--import-blue);
        box-shadow: 0 2px 4px rgba(41, 128, 185, 0.3);
    }
    .btn-import:hover { background-color: #1A5276; transform: translateY(-2px); }
    .index-section {
        background: white;
        padding: 20px 30px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        margin-bottom: 40px;
    }
    .index-section h2 {
        color: var(--primary);
        margin-top: 0;
        border-bottom: 2px solid var(--accent);
        padding-bottom: 10px;
    }
    .index-section ul {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        list-style-type: none;
        padding: 0;
    }
    .index-section a {
        text-decoration: none;
        color: var(--accent);
        font-weight: 500;
    }
    .index-section a:hover {
        text-decoration: underline;
    }
    table {
        width: 100%;
        background: white;
        border-collapse: collapse;
        margin-bottom: 50px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    caption {
        background-color: var(--primary);
        color: white;
        font-size: 1.3em;
        font-weight: 600;
        padding: 15px;
        text-align: left;
    }
    .table-desc {
        background-color: #FDFEFE;
        color: #555;
        font-style: italic;
        border-bottom: 2px solid var(--border-color) !important;
    }
    th {
        background-color: var(--bg-color);
        color: var(--secondary);
        font-weight: 600;
        text-align: left;
    }
    th, td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
    }
    tr:last-child td {
        border-bottom: none;
    }
    tr:hover {
        background-color: #F4F6F7;
    }
    .center {
        text-align: center;
    }
    .datatype {
        background: #E8F8F5;
        color: #117A65;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.9em;
        font-family: monospace;
    }
    abbr {
        text-decoration: none;
        border-bottom: 1px dotted #999;
        cursor: help;
    }
    .regex-input {
        width: 100%;
        min-width: 120px;
        padding: 8px;
        border: 1px solid transparent;
        border-radius: 4px;
        box-sizing: border-box;
        background-color: transparent;
        transition: all 0.2s ease;
        font-family: monospace;
        color: #e74c3c;
    }
    .regex-input:hover, .regex-input:focus {
        border: 1px solid var(--accent);
        background-color: white;
        outline: none;
        box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
    }
    .regex-input::placeholder {
        color: #bbb;
        font-family: 'Segoe UI', sans-serif;
        font-style: italic;
    }
    </style>
</head>
<body>
<div id="title-sect">
    <h1>mydb - Diccionario de Datos</h1>
</div>
<div class='controls'>
    <input type='file' id='csvFileInput' accept='.csv' style='display: none;' onchange='importFromCSV(event)' />
    <button class='btn-import' onclick='document.getElementById("csvFileInput").click()'>&#128194; Importar de Excel (CSV)</button>
    <button class='btn-excel' onclick='exportTablesToCSV("mydb_Diccionario.csv")'>&#128202; Guardar en Excel (CSV)</button>
</div>
<div class='index-section'>
<h2>Índice de Migraciones</h2>
<ul>
<li><a href='#roles'>roles</a></li>
<li><a href='#classroom'>classroom</a></li>
<li><a href='#school_years'>school_years</a></li>
<li><a href='#subjects'>subjects</a></li>
<li><a href='#tutors'>tutors</a></li>
<li><a href='#users'>users</a></li>
<li><a href='#devices'>devices</a></li>
<li><a href='#notification_preferences'>notification_preferences</a></li>
<li><a href='#directors'>directors</a></li>
<li><a href='#teachers'>teachers</a></li>
<li><a href='#addresses'>addresses</a></li>
<li><a href='#careers'>careers</a></li>
<li><a href='#academic_tutors'>academic_tutors</a></li>
<li><a href='#groups'>groups</a></li>
<li><a href='#students'>students</a></li>
<li><a href='#schedules'>schedules</a></li>
<li><a href='#attendance_settings'>attendance_settings</a></li>
<li><a href='#attendances'>attendances</a></li>
<li><a href='#group_academic_tutor'>group_academic_tutor</a></li>
<li><a href='#incidents'>incidents</a></li>
<li><a href='#student_tutor'>student_tutor</a></li>
<li><a href='#notifications'>notifications</a></li>
<li><a href='#claims'>claims</a></li>
<li><a href='#justifications'>justifications</a></li>
</ul>
</div>
<table id='roles'>
<caption>Tabla: roles</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>name</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='classroom'>
<caption>Tabla: classroom</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>name</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>building</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='school_years'>
<caption>Tabla: school_years</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>name</strong></td>
    <td><span class='datatype'>VARCHAR(50)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>start_date</strong></td>
    <td><span class='datatype'>DATE</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>end_date</strong></td>
    <td><span class='datatype'>DATE</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>status</strong></td>
    <td><span class='datatype'>ENUM('UPCOMING', 'ACTIVE', 'FINISHED')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>'UPCOMING'</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='subjects'>
<caption>Tabla: subjects</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>name</strong></td>
    <td><span class='datatype'>VARCHAR(100)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>code</strong></td>
    <td><span class='datatype'>VARCHAR(30)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>description</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='tutors'>
<caption>Tabla: tutors</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>first_name</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>second_name</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>first_surname</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>second_surname</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>phone</strong></td>
    <td><span class='datatype'>VARCHAR(10)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='users'>
<caption>Tabla: users</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>role_id</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>first_name</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>second_name</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>first_surname</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>second_surname</strong></td>
    <td><span class='datatype'>VARCHAR(45)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>email</strong></td>
    <td><span class='datatype'>VARCHAR(155)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>password</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>verified_at</strong></td>
    <td><span class='datatype'>DATETIME</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>photo</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>phone</strong></td>
    <td><span class='datatype'>VARCHAR(10)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>birth_date</strong></td>
    <td><span class='datatype'>DATE</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>gender</strong></td>
    <td><span class='datatype'>ENUM('M', 'F', 'OTRO')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='devices'>
<caption>Tabla: devices</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>mac_address</strong></td>
    <td><span class='datatype'>VARCHAR(20)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>ip</strong></td>
    <td><span class='datatype'>VARCHAR(30)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>classroom_id</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='notification_preferences'>
<caption>Tabla: notification_preferences</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>tutor_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>absences</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>lates</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>incidents</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>justifications</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>claims</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>announcements</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='directors'>
<caption>Tabla: directors</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>user_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='teachers'>
<caption>Tabla: teachers</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>user_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>speciality</strong></td>
    <td><span class='datatype'>VARCHAR(150)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='addresses'>
<caption>Tabla: addresses</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>street</strong></td>
    <td><span class='datatype'>VARCHAR(90)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>number</strong></td>
    <td><span class='datatype'>VARCHAR(31)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>neighborhood</strong></td>
    <td><span class='datatype'>VARCHAR(70)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>postal_code</strong></td>
    <td><span class='datatype'>VARCHAR(5)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>city</strong></td>
    <td><span class='datatype'>VARCHAR(30)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>state</strong></td>
    <td><span class='datatype'>VARCHAR(16)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>country</strong></td>
    <td><span class='datatype'>VARCHAR(6)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>users_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>tutors_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='careers'>
<caption>Tabla: careers</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>name</strong></td>
    <td><span class='datatype'>VARCHAR(150)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>short_name</strong></td>
    <td><span class='datatype'>VARCHAR(20)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>code</strong></td>
    <td><span class='datatype'>VARCHAR(30)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>directors_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='academic_tutors'>
<caption>Tabla: academic_tutors</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>teacher_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='groups'>
<caption>Tabla: groups</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>school_year_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>career_id</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>section</strong></td>
    <td><span class='datatype'>VARCHAR(5)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>grade</strong></td>
    <td><span class='datatype'>VARCHAR(5)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>shift</strong></td>
    <td><span class='datatype'>ENUM('MORNING', 'AFTERNOON', 'EVENING')</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='students'>
<caption>Tabla: students</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>user_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>group_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>student_number</strong></td>
    <td><span class='datatype'>VARCHAR(20)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>nfc_uid</strong></td>
    <td><span class='datatype'>VARCHAR(100)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>qr_uuid</strong></td>
    <td><span class='datatype'>VARCHAR(36)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='schedules'>
<caption>Tabla: schedules</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>school_year_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>group_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>subject_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>teacher_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>classroom_id</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>day_of_week</strong></td>
    <td><span class='datatype'>ENUM('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>start_time</strong></td>
    <td><span class='datatype'>TIME</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>end_time</strong></td>
    <td><span class='datatype'>TIME</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='attendance_settings'>
<caption>Tabla: attendance_settings</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>schedule_id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>present_tolerance_minutes</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>10</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>late_tolerance_minutes</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>30</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>allow_manual_attendance</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>0</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='attendances'>
<caption>Tabla: attendances</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>student_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>schedule_id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>devices_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>registered_at</strong></td>
    <td><span class='datatype'>DATETIME</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>status</strong></td>
    <td><span class='datatype'>ENUM('PRESENT', 'LATE', 'ABSENT', 'JUSTIFIED')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>method</strong></td>
    <td><span class='datatype'>ENUM('NFC', 'QR', 'MANUAL', 'SYSTEM')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='group_academic_tutor'>
<caption>Tabla: group_academic_tutor</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>group_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>academic_tutor_id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_active</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>assigned_at</strong></td>
    <td><span class='datatype'>DATE</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='incidents'>
<caption>Tabla: incidents</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>SMALLINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>reported_by_user_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>schedule_id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>title</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>description</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>severity</strong></td>
    <td><span class='datatype'>ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL')</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>evidence</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>incident_at</strong></td>
    <td><span class='datatype'>DATETIME</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='student_tutor'>
<caption>Tabla: student_tutor</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>tutor_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>student_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>relationship</strong></td>
    <td><span class='datatype'>VARCHAR(50)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_primary</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>0</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>receives_notifications</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>1</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='notifications'>
<caption>Tabla: notifications</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>student_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>tutor_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>user_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>title</strong></td>
    <td><span class='datatype'>VARCHAR(90)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>message</strong></td>
    <td><span class='datatype'>VARCHAR(350)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>type</strong></td>
    <td><span class='datatype'>ENUM('ABSENCE', 'LATE', 'INCIDENT', 'JUSTIFICATION', 'CLAIM', 'ANNOUNCEMENT', 'TEACHER_CLAIM')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>is_read</strong></td>
    <td><span class='datatype'>TINYINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>0</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>sent_at</strong></td>
    <td><span class='datatype'>DATETIME</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='claims'>
<caption>Tabla: claims</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>attendance_id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>reviewed_by_user_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>description</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>evidence</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>status</strong></td>
    <td><span class='datatype'>ENUM('PENDING', 'ACCEPTED', 'REJECTED')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>'PENDING'</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>
<table id='justifications'>
<caption>Tabla: justifications</caption>
<tr><td colspan='12' class='table-desc'><strong>Descripción:</strong> Sin descripción de la tabla.</td></tr>
<tr>
    <th>Nombre de la Columna</th>
    <th>Tipo de Dato</th>
    <th class='center'><abbr title='Primary Key'>PK</abbr></th>
    <th class='center'><abbr title='Not Null'>NN</abbr></th>
    <th class='center'><abbr title='Unique'>UQ</abbr></th>
    <th class='center'><abbr title='Binary'>BIN</abbr></th>
    <th class='center'><abbr title='Unsigned'>UN</abbr></th>
    <th class='center'><abbr title='Zero Fill'>ZF</abbr></th>
    <th class='center'><abbr title='Auto Increment'>AI</abbr></th>
    <th class='center'>Por Defecto</th>
    <th>Expresión Regular</th>
    <th>Comentarios</th>
</tr>
<tr>
    <td><strong>id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'>&#10004;</td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>attendance_id</strong></td>
    <td><span class='datatype'>INT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>justified_by_user_id</strong></td>
    <td><span class='datatype'>MEDIUMINT</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>reason</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>file</strong></td>
    <td><span class='datatype'>VARCHAR(255)</span></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>justified_at</strong></td>
    <td><span class='datatype'>DATETIME</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
<tr>
    <td><strong>status</strong></td>
    <td><span class='datatype'>ENUM('PENDING', 'ACCEPTED', 'REJECTED')</span></td>
    <td class='center'></td>
    <td class='center'>&#10004;</td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'></td>
    <td class='center'>'PENDING'</td>
    <td><input type='text' class='regex-input' placeholder='Escribir regex...' /></td>
    <td></td>
</tr>
</table>

<script>
// --- FUNCIÓN PARA EXPORTAR ---
function exportTablesToCSV(filename) {
    var csv = [];
    var tables = document.querySelectorAll("table");
    
    for (var t = 0; t < tables.length; t++) {
        var table = tables[t];
        var caption = table.querySelector("caption");
        if (caption) csv.push('"' + caption.innerText + '"');
        
        var rows = table.querySelectorAll("tr");
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            for (var j = 0; j < cols.length; j++) {
                var text = "";
                var inputField = cols[j].querySelector("input");
                
                if (inputField) {
                    text = inputField.value; 
                } else {
                    text = cols[j].innerText.replace(/✔/g, 'Sí'); 
                }
                
                text = text.replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            csv.push(row.join(","));
        }
        csv.push(""); 
    }

    var csvFile = new Blob(["\uFEFF" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// --- FUNCIONES PARA IMPORTAR ---
function importFromCSV(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        let text = e.target.result;
        // Eliminar BOM si existe para evitar errores en la lectura
        if (text.charCodeAt(0) === 0xFEFF) { text = text.slice(1); }
        
        const data = parseCSV(text);
        processImportedData(data);
        
        // Limpiar el input file por si quieren volver a cargar el mismo archivo
        event.target.value = ''; 
    };
    reader.readAsText(file, "UTF-8");
}

function parseCSV(str) {
    var arr = [];
    var quote = false;
    var row = 0, col = 0;
    for (var c = 0; c < str.length; c++) {
        var cc = str[c], nc = str[c+1];
        arr[row] = arr[row] || [];
        arr[row][col] = arr[row][col] || '';

        // Si es una comilla escapada ("") dentro de texto con comillas
        if (cc == '"' && quote && nc == '"') { arr[row][col] += cc; ++c; continue; }
        // Alternar el estado de comillas
        if (cc == '"') { quote = !quote; continue; }
        // Siguiente columna
        if (cc == ',' && !quote) { ++col; continue; }
        // Siguiente fila
        if (cc == '\r' && nc == '\n' && !quote) { ++row; col = 0; ++c; continue; }
        if (cc == '\n' && !quote) { ++row; col = 0; continue; }
        if (cc == '\r' && !quote) { ++row; col = 0; continue; }

        arr[row][col] += cc;
    }
    return arr;
}

function processImportedData(data) {
    let currentTableId = null;
    let regexCount = 0;

    for(let i=0; i<data.length; i++) {
        let row = data[i];
        if(!row || row.length === 0 || (row.length === 1 && row[0].trim() === "")) continue;

        // Detectar si la fila es el nombre de la tabla (ej. "Tabla: subjects")
        if(row[0].startsWith("Tabla: ")) {
            currentTableId = row[0].substring(7).trim();
            continue;
        }

        // Ignorar filas de encabezado o descripción
        if(row[0] === "Nombre de la Columna" || row[0].includes("Descripción:")) {
            continue; 
        }

        // Si estamos dentro de una tabla y tenemos las columnas suficientes
        if(currentTableId && row.length >= 11) {
            let colName = row[0].trim();
            let regexValue = row[10].trim(); // El índice 10 es "Expresión Regular"

            if(regexValue !== "") {
                let tableElement = document.getElementById(currentTableId);
                if(tableElement) {
                    let trs = tableElement.querySelectorAll("tr");
                    for(let j=0; j<trs.length; j++) {
                        let tds = trs[j].querySelectorAll("td");
                        if(tds.length > 0 && tds[0].innerText.trim() === colName) {
                            let input = trs[j].querySelector("input.regex-input");
                            if(input && input.value !== regexValue) {
                                input.value = regexValue;
                                regexCount++;
                                // Efecto visual para mostrar que se actualizó
                                input.style.backgroundColor = "#e8f8f5";
                                setTimeout(() => input.style.backgroundColor = "transparent", 1500);
                            }
                            break;
                        }
                    }
                }
            }
        }
    }
    alert("¡Importación exitosa! Se restauraron " + regexCount + " expresiones regulares.");
}
</script>
</body>
</html>