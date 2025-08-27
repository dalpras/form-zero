<?php
/* html.php */
return [
    'form' => <<<HTML
        <form {attributes}>
            {elements}
        </form>
        HTML,

    'input' => <<<HTML
        <input type="{type}" value="{value}" {attributes}/>
        HTML,

    'button' => <<<HTML
        <button {attributes}>{text}</button>
        HTML,

    'textarea' => <<<HTML
        <textarea {attributes}>{value}</textarea>
        HTML,

    'select' => <<<HTML
        <select {attributes}>{options}</select>
        HTML,

    'option' => <<<HTML
        <option value="{value}" {selected}>{text}</option>
        HTML,

    'fieldset' => <<<HTML
        <fieldset {attributes}>{legend}{content}</fieldset>
        HTML,

    'legend' => <<<HTML
        <legend>{text}</legend>
        HTML,

    'datepicker' => <<<HTML
        <div class="input-group mb-3" data-provide="datepicker" data-date-format="Y-m-d">
            <input type="{type}" value="{value}" data-input {attributes}/>
            <span class="input-group-text"><i class="fa-regular fa-calendar-alt"></i></span>
        </div>
        HTML,

    'form-element-checkbox' => <<<HTML
        <div class="form-check {class}">
            <input class="form-check-input" type="{type}" value="{value}" {checked} {attributes}/>
            <label class="form-check-label">{text}</label>
        </div>
        HTML,

    'feedback' => <<<HTML
        <div class="invalid-feedback d-block">{text}</div>
        HTML,

    'description-collapse' => <<<HTML
        <a href="#collapse-{id}" data-bs-toggle="collapse"><i class="fa-solid fa-info-circle fa-lg"></i></a>
        <div id="collapse-{id}" class="collapse">
            {description}
        </div>
        HTML,

    'description' => <<<HTML
        <div class="form-text text-muted small">{text}</div>
        HTML,

    'accordion' => <<<HTML
        <div id="{pid}" class="mb-4 accordion {class}" {attributes}>
            {content}
        </div>
        HTML,

    'accordion-item' => <<<HTML
        <div id="{id}" class="accordion-item {class}" {attributes}>
            {content}
        </div>
        HTML,

    'accordion-item-content' => <<<HTML
        <div class="row align-items-center" {attributes}>
            {trigger}
            {buttons}
            {mover}
        </div>
        <div id="collapse-{id}" class="accordion-collapse collapse {class}" data-bs-parent="#{pid}">
            <div class="accordion-body">
                {content}
            </div>
        </div>
        HTML,

    'item-trigger' => <<<HTML
        <div class="col">
            <h2 class="accordion-header p-0" id="header-{id}">
                <button class="accordion-button {class}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{id}" aria-expanded="false" aria-controls="collapse-{id}">
                    {text}
                </button>
            </h2>
        </div>
        HTML,

    'item-buttons' => <<<HTML
        <div class="col-auto">
            <div class="btn-group btn-group-sm pe-1">
                {buttons}
            </div>
        </div>
        HTML,

    'item-mover' => <<<HTML
        <div class="col-auto">
            <span class="fa-solid fa-arrows-alt fa-lg mx-2"></span>
        </div>
        HTML,

    'label' => <<<HTML
        <label class="{class} {required}" for="{for}">{text}</label>
        HTML,

    'content-wrapper' => <<<HTML
        <div class="{class}" {attributes}>
            {content}
        </div>
        HTML,
];