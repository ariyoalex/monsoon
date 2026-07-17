<?php

declare(strict_types=1);

namespace Monsoon\Modules\Forms;

final class FormRenderer
{
    public static function render(array $form): string
    {
        $formId = htmlspecialchars($form['id'], ENT_QUOTES, 'UTF-8');
        $successMsg = htmlspecialchars($form['success_message'] ?? 'Thank you!', ENT_QUOTES, 'UTF-8');

        $html = '<form class="monsoon-form" id="form-' . $formId . '" method="POST" action="/api/v1/forms/' . $formId . '/submit">';
        $html .= '<input type="hidden" name="_form_id" value="' . $formId . '">';
        $html .= '<input type="hidden" name="_start_time" value="' . time() . '">';

        if ($form['honeypot_enabled']) {
            $html .= '<div style="position:absolute;left:-9999px;" aria-hidden="true">';
            $html .= '<input type="text" name="_hp_field" tabindex="-1" autocomplete="off">';
            $html .= '</div>';
        }

        foreach ($form['fields'] as $field) {
            $label = htmlspecialchars($field['label'] ?? $field['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars($field['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $required = !empty($field['required']) ? ' required' : '';
            $type = $field['type'] ?? 'text';

            $html .= '<div class="mb-3">';
            $html .= '<label class="form-label fw-semibold" for="field-' . $name . '">' . $label . '</label>';

            switch ($type) {
                case 'textarea':
                    $html .= '<textarea class="form-control" id="field-' . $name . '" name="' . $name . '"' . $required . ' rows="4"></textarea>';
                    break;
                case 'select':
                    $html .= '<select class="form-select" id="field-' . $name . '" name="' . $name . '"' . $required . '>';
                    foreach (($field['options'] ?? []) as $opt) {
                        $optVal = htmlspecialchars($opt, ENT_QUOTES, 'UTF-8');
                        $html .= '<option value="' . $optVal . '">' . $optVal . '</option>';
                    }
                    $html .= '</select>';
                    break;
                case 'checkbox':
                    $html .= '<div class="form-check">';
                    $html .= '<input type="checkbox" class="form-check-input" id="field-' . $name . '" name="' . $name . '" value="1"' . $required . '>';
                    $html .= '<label class="form-check-label" for="field-' . $name . '">' . $label . '</label>';
                    $html .= '</div>';
                    break;
                case 'radio':
                    foreach (($field['options'] ?? []) as $opt) {
                        $optVal = htmlspecialchars($opt, ENT_QUOTES, 'UTF-8');
                        $html .= '<div class="form-check">';
                        $html .= '<input type="radio" class="form-check-input" name="' . $name . '" value="' . $optVal . '"' . $required . '>';
                        $html .= '<label class="form-check-label">' . $optVal . '</label>';
                        $html .= '</div>';
                    }
                    break;
                case 'email':
                    $html .= '<input type="email" class="form-control" id="field-' . $name . '" name="' . $name . '"' . $required . '>';
                    break;
                case 'number':
                    $html .= '<input type="number" class="form-control" id="field-' . $name . '" name="' . $name . '"' . $required . '>';
                    break;
                case 'tel':
                    $html .= '<input type="tel" class="form-control" id="field-' . $name . '" name="' . $name . '"' . $required . '>';
                    break;
                case 'url':
                    $html .= '<input type="url" class="form-control" id="field-' . $name . '" name="' . $name . '"' . $required . '>';
                    break;
                case 'date':
                    $html .= '<input type="date" class="form-control" id="field-' . $name . '" name="' . $name . '"' . $required . '>';
                    break;
                default:
                    $html .= '<input type="text" class="form-control" id="field-' . $name . '" name="' . $name . '"' . $required . '>';
            }

            $html .= '</div>';
        }

        $html .= '<button type="submit" class="btn btn-primary">Submit</button>';
        $html .= '</form>';

        $html .= '<script>';
        $html .= '(function(){';
        $html .= 'var f=document.getElementById("form-' . $formId . '");';
        $html .= 'if(!f)return;';
        $html .= 'f.addEventListener("submit",function(e){';
        $html .= 'e.preventDefault();';
        $html .= 'var fd=new FormData(f);';
        $html .= 'fetch(f.action,{method:"POST",body:fd})';
        $html .= '.then(function(r){return r.json()})';
        $html .= '.then(function(j){';
        $html .= 'if(j.data&&j.data.success){';
        $html .= 'f.innerHTML="<div class=\'alert alert-success\'>' . $successMsg . '</div>";';
        $html .= '}else{';
        $html .= 'alert(j.error?.message||"Submission failed.");';
        $html .= '}';
        $html .= '})';
        $html .= '.catch(function(){alert("Network error.")});';
        $html .= '});';
        $html .= '})();';
        $html .= '</script>';

        return $html;
    }
}
