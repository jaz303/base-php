<?php
// Example Use:
// 
// $m = new Blade_ContactMailer;
// $m->text('title')->trim()->required();
// $m->text('name')->trim()->value('who?')->required();
// $m->text('address_1')->trim()->required();
// $m->text('address_2')->trim();
// $m->text('city')->trim()->required();
// $m->text('postcode')->trim()->to_upper()->required();
// $m->text('telephone')->trim()->required();
// $m->text('email')->trim()->required()->match('/@/');
// $m->text('email_confirmation');
// $m->textarea('question', 10, 10)->required();
// 
// $m->to('jason@magiclamp.co.uk');
// $m->from('no-reply@foobar.com');
// $m->subject('Website enquiry');
// 
// $template = <<<TEMPLATE
// From: {title} {name}
// 
// Address
// -------
// {address_1}
// {address_2}
// {city}
// {postcode}
// 
// Telephone: {telephone}
// Email: {email}
// 
// Question
// --------
// {question}
// TEMPLATE;
// 
// $m->template($template);
// 
// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     if ($m->handle($_POST)) {
//         $_TPL['success'] = true;
//     } else {
//         $_TPL['errors'] = $m->get_errors();
//     }
// }
// 
// $_TPL['form'] = $m;
//
abstract class Contact_Form
{
    public static function humanise($field) {
        return ucfirst(strtolower(str_replace(array('_', '-'), ' ', $field)));
    }
    
    private $fields     = array();
    private $errors     = array();
    
    public function text($name, $size = null, $maxlength = null) {
        $f = new Contact_Form_Text($name, $size, $maxlength);
        $this->fields[$name] = $f;
        return $f;
    }
    
    public function textarea($name, $rows = null, $cols = null) {
        $f = new Contact_Form_Textarea($name, $rows, $cols);
        $this->fields[$name] = $f;
        return $f;
    }
    
    public function select($name, $options) {
        $f = new Contact_Form_Select($name, $options);
        $this->fields[$name] = $f;
        return $f;
    }
    
    public function date($name) {
        $f = new Contact_Form_Date($name);
        $this->fields[$name] = $f;
        return $f;
    }
    
    public function time($name, $interval, $start = '00:00', $end = '24:00') {
        $f = new Contact_Form_Time($name, $interval, $start, $end);
        $this->fields[$name] = $f;
        return $f;
    }
    
    public function checkbox($name, $caption = '') {
        $f = new Contact_Form_Checkbox($name, $caption);
        $this->fields[$name] = $f;
        return $f;
    }
    
    public function upload($name) {
        $f = new Contact_Form_File($name);
        $this->fields[$name] = $f;
        return $f;
    }
    
    public function handle($data, $files = array()) {
        
        $this->errors = array();
        
        foreach ($files as $k => $file_info) {
            $data[$k] = $file_info;
        }
        
        foreach ($this->fields as $k => $field) {
            $field->set_value(@$data[$k]);
            $error = $field->process_and_validate();
            if ($error !== true) {
                $this->errors[] = self::humanise($k) . ' ' . $error;
            }
        }

        foreach (array_keys($this->fields) as $field) {
            if (preg_match('/(.*?)_confirmation$/', $field, $matches)) {
                $original   = $this->fields[$matches[1]]->get_value();
                $confirm    = $this->fields[$field]->get_value();
                if (strcmp($original, $confirm) !== 0) {
                    $this->errors[] = self::humanise($matches[1]) . ' does not match confirmation';
                }
            }
        }
        
        if (count($this->errors) == 0) {
            $this->commit();
            return true;
        } else {
            return false;
        }
        
    }
    
    public function get_data() {
        $data = array();
        foreach ($this->get_fields() as $k => $v) {
            $data[$k] = $v->get_value();
        }
        return $data;
    }
    
    public function get_formatted_data() {
        $data = array();
        foreach ($this->get_fields() as $k => $v) {
            $data[$k] = $v->format_value();
        }
        return $data;
    }
    
    public function get_fields() {
        return $this->fields;
    }
    
    public function get_errors() {
        return $this->errors;
    }
    
    public function render($name) {
        return $this->fields[$name]->render();
    }
    
    //
    // Substitute
    
    private $sub_call = null;
    
    protected function substitute($text, $callback = null) {
        $this->sub_call = $callback;
        return preg_replace_callback('/\{(\w+)\}/', array($this, 'do_substitute'), $text);
    }
    
    private function do_substitute($matches) {
        $value = $this->fields[$matches[1]]->format_value();
        if ($this->sub_call) {
            $method = $this->sub_call;
            $value = $this->$method($value);
        }
        return $value;
    }
    
    //
    //
    
    public function to_html_table($options = array()) {
        
        $html = '';
        
        if (isset($options['with_errors']) && $options['with_errors']) {
            $errors = $this->get_errors();
            if (count($errors)) {
                $html .= "<div class='flash error'><ul>\n";
                foreach ($errors as $e) {
                    $html .= "<li>" . $e . "</li>\n";
                }
                $html .= "</ul></div>\n";
            }
        }
        
        $class = isset($options['class']) ? $options['class'] : '';
        
        $html .= "<table class='{$class}'>\n";
        
        foreach ($this->get_fields() as $field) {
            $html .= "<tr><th>" . $field->get_label() . "</th><td>" . $field->render() . "</td></tr>\n";
        }
        
        if (isset($options['submit_text'])) {
            $html .= "<tr><th>&nbsp;</th><td><input type='submit' value='{$options['submit_text']}' /></td></tr>";
        }
        
        $html .= "</table>\n";
        
        return $html;
        
    }
}

class Contact_Form_Field
{
    protected $name;
    protected $value        = null;
    
    private $label          = null;
    private $trim           = false;
    private $to_upper       = false;
    private $to_lower       = false;
    protected $required     = false;
    private $match          = null;
    
    public function __construct($name) {
        $this->name = $name;
    }
    
    public function value($v) { $this->set_value($v); return $this; }
    public function label($l) { $this->label = $l; return $this; }
    public function trim() { $this->trim = true; return $this; }
    public function to_upper() { $this->to_upper = true; return $this; }
    public function to_lower() { $this->to_lower = true; return $this; }
    public function required() { $this->required = true; return $this; }
    public function match($pattern) { $this->match = $pattern; return $this; }
    
    public function get_value() { return $this->value; }
    public function set_value($v) { $this->value = $v; }
    public function format_value() { return $this->value; }
    
    public function get_label() {
        return $this->label === null ? Contact_Form::humanise($this->name) : $this->label;
    }
    
    public function process_and_validate() {
        
        $error = true;
        $value = $this->get_value();
        $empty = false;
        
        try {
            
            if ($this->trim) { $value = trim($value); }
            if ($this->to_upper) { $value = strtoupper($value); }
            if ($this->to_lower) { $value = strtolower($value); }
            
            if (strlen($value) == 0) {
                $empty = true;
                if ($this->required) {
                    throw new Exception('cannot be blank');
                }
            }
            
            if (!$empty && $this->match && !preg_match($this->match, $value)) {
                throw new Exception('is invalid');
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        $this->set_value($value);
        
        return $error;
        
    }
    
}

class Contact_Form_Text extends Contact_Form_Field
{
    public function __construct($name, $size, $maxlength) {
        parent::__construct($name);
        $this->size = $size;
        $this->maxlength = $maxlength;
    }
    
    public function render() {
        $html = "<input name='{$this->name}'";
        if ($this->maxlength) $html .= " maxlength='{$this->maxlength}'";
        if ($this->size) $html .= " size='{$this->size}'";
        $html .= " value='" . htmlentities($this->get_value()) . "'/>";
        return $html;
    }
}

class Contact_Form_Textarea extends Contact_Form_Field
{
    public function __construct($name, $rows, $cols) {
        parent::__construct($name);
        $this->rows = $rows;
        $this->cols = $cols;
    }
    
    public function render() {
        $html = "<textarea name='{$this->name}'";
        if ($this->rows) $html .= " rows='{$this->rows}'";
        if ($this->cols) $html .= " cols='{$this->cols}'";
        $html .= '>' . htmlentities($this->get_value()) . '</textarea>';
        return $html;
    }
}

class Contact_Form_Select extends Contact_Form_Field
{
    public function __construct($name, $options) {
        parent::__construct($name);
        $this->options = (array) $options;
    }
    
    public function render() {
        $html = "<select name='{$this->name}'>";
        foreach ($this->options as $v) {
            $sel = ($v == $this->get_value()) ? ' selected="true"' : '';
            $html .= "<option{$sel}>{$v}</option>";
        }
        $html .= "</select>";
        return $html;
    }
}

class Contact_Form_Date extends Contact_Form_Field
{
    private $format = 'd/m/Y';
    private $years  = null;
    
    public function __construct($name) {
        parent::__construct($name);
        $this->value = array('day' => date('d'), 'month' => date('m'), 'year' => date('Y'));
    }
    
    public function format($f) { $this->format = $f; return $this; }
    public function years($min, $max) { $this->years = array($min, $max); return $this; }
    
    public function render() {

        $html = '';
        $date = $this->get_value();

        $html .= "<select name='{$this->name}[day]'>";
        foreach (range(1, 31) as $d) {
            $sel = $d == $date['day'] ? ' selected="true"' : '';
            $html .= "<option{$sel}>{$d}</option>";
        }
        $html .= "</select>";
        
        $html .= " <select name='{$this->name}[month]'>";
        foreach (range(1, 12) as $m) {
            $sel = $m == $date['month'] ? ' selected="true"' : '';
            $html .= "<option{$sel}>{$m}</option>";
        }
        $html .= "</select>";
        
        if ($this->years) {
            $html .= " <select name='{$this->name}[year]'>";
            foreach (range($this->years[0], $this->years[1]) as $y) {
                $sel = $y == $date['year'] ? ' selected="true"' : '';
                $html .= "<option{$sel}>{$y}</option>";
            }
            $html .= "</select>";
        } else {
            $html .= " <input type='text' size='4' value='{$date['year']}' name='{$this->name}[year]' />";
        }
        
        return $html;
    
    }
    
    public function process_and_validate() {
        
        $error = true;
        $value = $this->get_value();
        
        if (!isset($value['day']) || !isset($value['month']) || !isset($value['year'])) {
            $error = "is invalid";
        }
        
        if (!checkdate($value['month'], $value['day'], $value['year'])) {
            $error = "is invalid";
        }
        
        return $error;

    }
    
    public function format_value() {
        return date($this->format, $this->build_date());
    }
    
    protected function build_date() {
        return mktime(0, 0, 0, $this->value['month'], $this->value['day'], $this->value['year']);
    }
}

class Contact_Form_Time extends Contact_Form_Select
{
    public function __construct($name, $interval, $from = '00:00', $to = '24:00') {
        
        list($h, $m) = explode(':', $from);
        list($th, $tm) = explode(':', $to);
        
        $options = array();
        while ($h < $th || ($h == $th && $m <= $tm)) {
            $options[] = sprintf("%02d:%02d", $h, $m);
            $m += $interval;
            $h += floor($m / 60);
            $m %= 60;
        }
        
        parent::__construct($name, $options);
    
    }
}

class Contact_Form_Checkbox extends Contact_Form_Field
{
    protected $value = false;
    private $caption = '';
    
    public function __construct($name, $caption = '') {
        parent::__construct($name);
        $this->caption = $caption;
        if ($this->caption) $this->label('');
    }
    
    public function render() {
        $checked = $this->value ? ' checked="checked"' : '';
        return "<input type='checkbox' name='{$this->name}' value='1'{$checked}/> {$this->caption}";
    }
    
    public function set_value($v) { $this->value = (bool) $v; }
    
    public function format_value() { return $this->value ? 'Yes' : 'No'; }
}

class Contact_Form_File extends Contact_Form_Field
{
    private $path         = null;
  
    public function __construct($name) {
        parent::__construct($name);
    }
  
    public function process_and_validate() {
        if ($this->required && $this->value === null) {
            return "must be uploaded";
        } else {
            return true;
        }
    }
  
    public function render() {
        return "<input name='{$this->name}' type='file' />";
    }
  
    public function set_value($v) {
        if (is_array($v) && is_uploaded_file($v['tmp_name'])) {
            $this->value = $v['name'];
            $this->path  = $v['tmp_name'];
        } else {
            $this->value = null;
        }
    }

    public function get_path() {
        return $this->path;
    }
}

class Contact_Mailer extends Contact_Form
{
    private $to         = 'someone@somewhere.com';
    private $from       = null;
    private $subject    = 'Web Enquiry';
    private $template   = '';
    private $html       = false;
    
    public function to($to) { $this->to = $to; return $this; }
    public function from($from) { $this->from = $from; return $this; }
    public function subject($subject) { $this->subject = $subject; return $this; }
    public function template($template, $html = false) { $this->template = $template; $this->html = $html; return $this; }
    
    public function commit() {
        
        $headers    = '';
        $subject    = $this->substitute($this->subject);
        $body       = $this->substitute($this->template, 'substitute_body_value');
        
        if ($this->html) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        }
        
        if ($this->from !== null) {
            $headers .= "From: {$this->from}\r\n";
        }
        
        foreach ($this->get_fields() as $field) {
            // TODO: handle attachments
            // if ($field instanceof Blade_ContactForm_File) {
            //     $path     = $field->get_path();
            //     $filename = $field->get_value();
            //     if ($path && $filename) {
            //         $m->AddAttachment($path, $filename);
            //     }
            // }
        }
        
        @mail($to, $subject, $body, $headers);
        
    }
    
    protected function substitute_body_value($value) {
        if ($this->html) {
            return htmlentities($value);
        } else {
            return $value;
        }
    }
}

class Contact_CSV extends Contact_Form
{
    private $file;
    
    public function file($file) { $this->file = $file; return $this; }
    
    public function commit() {
        
        if (!$fh = @fopen($this->file, 'a')) {
            throw new Exception("Couldn't open CSV file {$this->file} for writing");
        }
        
        if (!@flock($fh, LOCK_EX)) {
            throw new Exception("Couldn't acquire lock on CSV file");
        }
        
        fputcsv($fh, $this->get_formatted_data());
        
        fclose($fh);
        
    }
}
?>