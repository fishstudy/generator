<?php
/**
 * 获取表的注释
 * @param       $dbh
 * @param       $table
 * @param array $filter_columns
 */
function get_table_comment($dbh, $table, $filter_columns = array())
{
    $sComment = '';
    $table = 't_' . preg_replace('/^t_/i', '', trim($table));
    $sql   = sprintf("show table status like '%s'", $table);
    $stmt  = $dbh->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(!empty($results)) {
        if($results[0]['Name'] == $table){
            $sComment = $results[0]['Comment'];
        }
    }

    return $sComment;
}

/**
 * 获取表所有字段
 * @param $dbh
 * @param $table
 * @return array
 */
function fields_create($dbh, $table, $filter_columns = array())
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf('SHOW FULL COLUMNS FROM %s', $table);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $row) {
            if (in_array($row['Field'], $filter_columns)) {
                continue;
            }
            $fields[$row['Field']]['fieldName'] = $row['Field'];
            $length = strpos($row['Type'],'(');
            if($length === false) {
                $length = strlen($row['Type']);
            }
            $val_type = substr($row['Type'], 0, $length);
            $fields[$row['Field']]['Type'] = field_format($val_type);
            $fields[$row['Field']]['Comment'] = $row['Comment'];
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

/**
 * 获取表所有字段
 * @param $dbh
 * @param $table
 * @return array
 */
function fields_types($dbh, $table, $filter_columns = array())
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf('desc %s', $table);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $result) {
            if (in_array($result['Field'], $filter_columns)) {
                continue;
            }
            $fields[$result['Field']]           = $result;
            $fields[$result['Field']]['format'] = field_format($result['Type']);
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}




/**
 * 根据数据库字段类型转化为验证类型
 * @param $type
 * @return mixed
 */
function field_format($type)
{
    $formats = array(
        'tinyint'    => 'numeric',
        'smallint'   => 'numeric',
        'mediumint'  => 'numeric',
        'int'        => 'numeric',
        'bigint'     => 'numeric',
        'float'      => 'numeric',
        'double'     => 'numeric',
        'decimal'    => 'numeric',
        'char'       => 'string',
        'varchar'    => 'string',
        'tinytext'   => 'string',
        'text'       => 'string',
        'mediumtext' => 'string',
        'longtext'   => 'string',
        'date'       => 'string',
        'time'       => 'string',
        'datetime'   => 'string',
        'timestamp'  => 'string',
        'enum'       => 'string',
    );
    preg_match_all('/[a-zA-z]+/', $type, $matches);
    return $formats[$matches[0][0]];
}

/**
 * 获取表所有字段
 * @param $dbh
 * @param $table
 * @return array
 */
function fields($dbh, $table)
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf('desc %s', $table);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $result) {
            $fields[] = $result['Field'];
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

/**
 * 规范uri
 * @param $uri
 * @return string
 */
function get_uri_specification($uri)
{
    return ucfirst(preg_replace_callback('/[\/]{1}([a-zA-Z])/', function ($match) {
        return '\\' . strtoupper($match[1]);
    }, str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $uri)))));
}

/**
 * 拼接字段验证字符串
 * @param array $fields
 * @return string
 */
function fields_validate_format($fields = array())
{
    $str = '';
    foreach ($fields as $key => $field) {
        $str .= "'" . $key . "' => ['required' => true, 'type' => '" . $field['format'] . "', 'message' => ['required' => '缺少" . $key . "参数', 'type' => '" . $key . "参数类型错误']],\r\n\t\t\t";
    }
    return $str;
}

/**
 * 获取数据表主键
 * @param $dbh
 * @param $dbname
 * @param $table
 * @return string
 */
function get_primary_fields($dbh, $dbname, $table)
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf("SELECT * FROM information_schema.KEY_COLUMN_USAGE
                         WHERE table_name='%s' AND TABLE_SCHEMA='%s' AND CONSTRAINT_NAME = 'PRIMARY'", $table, $dbname);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $result) {
            $fields[] = $result['COLUMN_NAME'];
        }
        if (empty($fields)) {
            return ['iAutoId'];
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}
