<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Request;
use App\Service\LanguageService;
use App\Service\Translator;

/*
Przełączanie języka interfejsu: POST /language (pola lang, back, csrfToken).
Kontroler tylko waliduje dane i deleguje zapis do LanguageService.
*/
final class LanguageController extends AppController
{
    public function __construct(Translator $translator, private readonly LanguageService $languages)
    {
        parent::__construct($translator);
    }

    public function change(Request $request): void
    {
        $language = $request->post('lang');

        if (!is_string($language) || !Translator::isSupported($language)) {
            throw HttpException::badRequest('Unsupported language');
        }

        $this->languages->remember($language, $request->isSecure());
        $this->redirect($this->languages->safeBackPath($request->post('back')));
    }
}
