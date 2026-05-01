<?php declare(strict_types = 1);

namespace App\Model\Form\Enum\Class;

enum FormClass: string
{
	case LABEL = 'form-label';
	case INPUT = 'form-control';
	case SELECT = 'form-select';
	case TEXTAREA_CKEDITOR = 'ckeditor-textarea';
}