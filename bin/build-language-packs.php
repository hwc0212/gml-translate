<?php
/** Generate WordPress language catalogs for the standalone admin UI. */

$root = dirname( __DIR__ );
$dir  = $root . '/languages';
if ( ! is_dir( $dir ) ) {
    mkdir( $dir, 0775, true );
}

$strings = extract_i18n_strings( $root );
sort( $strings, SORT_STRING );

$common = [
    'Settings',
    'Language Switcher',
    'Translations',
    'Exclusion Rules',
    'Glossary',
    'Main Configuration',
    'Multilingual Site',
    'AI Translation',
    'Translation Engine',
    'Original Language',
    'Destination Languages',
    'Save Settings',
    'API Key is configured',
    'Start Auto-Translate',
    'Stop Auto-Translate',
    'Translation Progress',
    'Running',
    'Paused',
    'Pending',
    'Failed',
    'Completed',
    'Save',
    'Cancel',
    'Edit',
    'Delete',
    'Search translations...',
    'Source Text',
    'Translation',
    'Status',
    'Actions',
    'No translations found.',
    'Loading...',
    'Translation saved.',
    'All Languages',
    'Advanced Settings',
];

$translations = [
    'zh_CN' => array_combine( $common, [
        '设置', '语言切换器', '翻译管理', '排除规则', '术语表', '主要配置', '多语言站点', 'AI 翻译', '翻译引擎', '源语言', '目标语言', '保存设置', 'API Key 已配置', '开始自动翻译', '停止自动翻译', '翻译进度', '运行中', '已暂停', '等待中', '失败', '已完成', '保存', '取消', '编辑', '删除', '搜索翻译...', '原文', '译文', '状态', '操作', '未找到翻译。', '加载中...', '翻译已保存。', '所有语言', '高级设置',
    ] ),
    'zh_TW' => array_combine( $common, [
        '設定', '語言切換器', '翻譯管理', '排除規則', '術語表', '主要設定', '多語言網站', 'AI 翻譯', '翻譯引擎', '來源語言', '目標語言', '儲存設定', 'API Key 已設定', '開始自動翻譯', '停止自動翻譯', '翻譯進度', '執行中', '已暫停', '等待中', '失敗', '已完成', '儲存', '取消', '編輯', '刪除', '搜尋翻譯...', '原文', '譯文', '狀態', '操作', '找不到翻譯。', '載入中...', '翻譯已儲存。', '所有語言', '進階設定',
    ] ),
    'de_DE' => array_combine( $common, [
        'Einstellungen', 'Sprachumschalter', 'Übersetzungen', 'Ausschlussregeln', 'Glossar', 'Hauptkonfiguration', 'Mehrsprachige Website', 'KI-Übersetzung', 'Übersetzungsdienst', 'Ausgangssprache', 'Zielsprachen', 'Einstellungen speichern', 'API-Schlüssel ist konfiguriert', 'Automatische Übersetzung starten', 'Automatische Übersetzung stoppen', 'Übersetzungsfortschritt', 'Läuft', 'Pausiert', 'Ausstehend', 'Fehlgeschlagen', 'Abgeschlossen', 'Speichern', 'Abbrechen', 'Bearbeiten', 'Löschen', 'Übersetzungen suchen...', 'Ausgangstext', 'Übersetzung', 'Status', 'Aktionen', 'Keine Übersetzungen gefunden.', 'Wird geladen...', 'Übersetzung gespeichert.', 'Alle Sprachen', 'Erweiterte Einstellungen',
    ] ),
    'fr_FR' => array_combine( $common, [
        'Réglages', 'Sélecteur de langue', 'Traductions', 'Règles d’exclusion', 'Glossaire', 'Configuration principale', 'Site multilingue', 'Traduction IA', 'Moteur de traduction', 'Langue source', 'Langues cibles', 'Enregistrer les réglages', 'La clé API est configurée', 'Démarrer la traduction automatique', 'Arrêter la traduction automatique', 'Progression de la traduction', 'En cours', 'En pause', 'En attente', 'Échec', 'Terminé', 'Enregistrer', 'Annuler', 'Modifier', 'Supprimer', 'Rechercher des traductions...', 'Texte source', 'Traduction', 'État', 'Actions', 'Aucune traduction trouvée.', 'Chargement...', 'Traduction enregistrée.', 'Toutes les langues', 'Réglages avancés',
    ] ),
    'es_ES' => array_combine( $common, [
        'Ajustes', 'Selector de idioma', 'Traducciones', 'Reglas de exclusión', 'Glosario', 'Configuración principal', 'Sitio multilingüe', 'Traducción con IA', 'Motor de traducción', 'Idioma de origen', 'Idiomas de destino', 'Guardar ajustes', 'La clave API está configurada', 'Iniciar traducción automática', 'Detener traducción automática', 'Progreso de traducción', 'En ejecución', 'En pausa', 'Pendiente', 'Fallido', 'Completado', 'Guardar', 'Cancelar', 'Editar', 'Eliminar', 'Buscar traducciones...', 'Texto de origen', 'Traducción', 'Estado', 'Acciones', 'No se encontraron traducciones.', 'Cargando...', 'Traducción guardada.', 'Todos los idiomas', 'Ajustes avanzados',
    ] ),
    'pt_BR' => array_combine( $common, [
        'Configurações', 'Seletor de idioma', 'Traduções', 'Regras de exclusão', 'Glossário', 'Configuração principal', 'Site multilíngue', 'Tradução por IA', 'Mecanismo de tradução', 'Idioma de origem', 'Idiomas de destino', 'Salvar configurações', 'A chave de API está configurada', 'Iniciar tradução automática', 'Parar tradução automática', 'Progresso da tradução', 'Em execução', 'Pausado', 'Pendente', 'Falhou', 'Concluído', 'Salvar', 'Cancelar', 'Editar', 'Excluir', 'Pesquisar traduções...', 'Texto de origem', 'Tradução', 'Status', 'Ações', 'Nenhuma tradução encontrada.', 'Carregando...', 'Tradução salva.', 'Todos os idiomas', 'Configurações avançadas',
    ] ),
    'ja' => array_combine( $common, [
        '設定', '言語スイッチャー', '翻訳', '除外ルール', '用語集', '基本設定', '多言語サイト', 'AI 翻訳', '翻訳エンジン', '原文の言語', '翻訳先言語', '設定を保存', 'API キー設定済み', '自動翻訳を開始', '自動翻訳を停止', '翻訳の進捗', '実行中', '一時停止', '保留中', '失敗', '完了', '保存', 'キャンセル', '編集', '削除', '翻訳を検索...', '原文', '翻訳', '状態', '操作', '翻訳が見つかりません。', '読み込み中...', '翻訳を保存しました。', 'すべての言語', '詳細設定',
    ] ),
    'ko_KR' => array_combine( $common, [
        '설정', '언어 전환기', '번역', '제외 규칙', '용어집', '기본 구성', '다국어 사이트', 'AI 번역', '번역 엔진', '원본 언어', '대상 언어', '설정 저장', 'API 키가 설정됨', '자동 번역 시작', '자동 번역 중지', '번역 진행률', '실행 중', '일시 중지', '대기 중', '실패', '완료', '저장', '취소', '편집', '삭제', '번역 검색...', '원문', '번역', '상태', '작업', '번역을 찾을 수 없습니다.', '불러오는 중...', '번역이 저장되었습니다.', '모든 언어', '고급 설정',
    ] ),
    'ru_RU' => array_combine( $common, [
        'Настройки', 'Переключатель языка', 'Переводы', 'Правила исключения', 'Глоссарий', 'Основная конфигурация', 'Многоязычный сайт', 'ИИ-перевод', 'Сервис перевода', 'Исходный язык', 'Целевые языки', 'Сохранить настройки', 'API-ключ настроен', 'Запустить автоперевод', 'Остановить автоперевод', 'Ход перевода', 'Выполняется', 'Приостановлено', 'Ожидает', 'Ошибка', 'Завершено', 'Сохранить', 'Отмена', 'Изменить', 'Удалить', 'Поиск переводов...', 'Исходный текст', 'Перевод', 'Статус', 'Действия', 'Переводы не найдены.', 'Загрузка...', 'Перевод сохранён.', 'Все языки', 'Расширенные настройки',
    ] ),
    'ar' => array_combine( $common, [
        'الإعدادات', 'مبدّل اللغة', 'الترجمات', 'قواعد الاستبعاد', 'المصطلحات', 'الإعدادات الرئيسية', 'موقع متعدد اللغات', 'ترجمة بالذكاء الاصطناعي', 'محرك الترجمة', 'لغة المصدر', 'اللغات المستهدفة', 'حفظ الإعدادات', 'تم إعداد مفتاح API', 'بدء الترجمة التلقائية', 'إيقاف الترجمة التلقائية', 'تقدم الترجمة', 'قيد التشغيل', 'متوقف مؤقتًا', 'قيد الانتظار', 'فشل', 'مكتمل', 'حفظ', 'إلغاء', 'تعديل', 'حذف', 'بحث في الترجمات...', 'النص المصدر', 'الترجمة', 'الحالة', 'الإجراءات', 'لم يتم العثور على ترجمات.', 'جارٍ التحميل...', 'تم حفظ الترجمة.', 'كل اللغات', 'إعدادات متقدمة',
    ] ),
];

$controls_zh = [
    'The saved API key cannot be decrypted on this site. Re-enter and save the key. No API request was sent; existing translations are unaffected.' => '此站点无法解密已保存的 API Key。请重新输入并保存。本次未发送 API 请求，已有译文不受影响。',
    'This test does not start or resume translation.' => '本次测试不会启动或恢复翻译。',
    'Tested saved configuration: %1$s / %2$s. Key contents are never displayed.' => '本次测试使用已保存配置：%1$s / %2$s。不会显示密钥内容。',
    'Saved. Leave blank to keep unchanged.' => '已保存，留空则保持不变。',
    'Stored and readable. This is not a connection check.' => '已保存且可读取，不代表连接验证通过。',
    'Saved key is unreadable. Re-enter it.' => '已保存的密钥无法读取，请重新输入。',
    'Unknown AI provider. Settings were not saved.' => '无法识别 AI 服务商，设置未保存。',
    'AI provider selection could not be saved. Check database writes before testing the connection.' => 'AI 服务商选择保存失败，请先检查数据库写入，再测试连接。',
    'API key encrypted, saved, and read back successfully. Use Test Saved AI Connection to verify provider access. Translation remains paused.' => 'API Key 已加密保存并回读核对成功。请测试已保存的 AI 连接，确认服务商访问正常。翻译仍保持暂停。',
    'API key save could not be verified. Check the key for whitespace, OpenSSL availability, and database writes. No connection test was sent.' => 'API Key 保存未通过核对。请检查密钥是否含空白字符、OpenSSL 是否可用、数据库能否写入。本次未发送连接测试。',
    'Translation activation blocked' => '已阻止重复激活翻译模块',
    'GML SEO is already providing multilingual translation. Use its Translation page, or deactivate GML SEO before activating GML Translate. Existing translation data has not been deleted.' => 'GML SEO 已在提供多语言翻译。请使用其 Translation 页面，或先停用 GML SEO，再启用 GML Translate。现有翻译数据未被删除。',
    'Paused' => '已暂停',
    'Translations' => '译文管理',
    'Language Switcher' => '语言切换器',
    'Exclusion Rules' => '排除规则',
    'Glossary' => '术语表',
    'FROM / TO' => '源语言 / 目标语言',
    'TRANSLATED' => '已翻译',
    'PROGRESS' => '进度',
    'STATUS' => '状态',
    'ACTIONS' => '操作',
    'pending' => '待处理',
    'failed' => '失败',
    'Start' => '开始',
    'Pause' => '暂停',
    'Manage Translations' => '管理译文',
    'Translation Queue' => '翻译队列',
    'Content Scan' => '内容扫描',
    'Start Translation' => '开始翻译',
    'Pause Translation' => '暂停翻译',
    'Scan Website Content' => '扫描网站内容',
    'Stop Scan' => '停止扫描',
    'Refresh Page Cache' => '刷新页面缓存',
    'Queue Status' => '队列状态',
    'Pending Segments' => '待处理文本段',
    'Failed Segments' => '失败文本段',
    'Last Worker Activity' => '最近任务活动时间',
    'Not recorded yet' => '尚无记录',
    'AI unavailable' => 'AI 不可用',
    'Safety paused' => '安全暂停',
    'Pausing after current batch' => '当前批次结束后暂停',
    'Processing batch' => '正在处理批次',
    'Idle' => '空闲',
    'Scheduled' => '已排程',
    'Not scheduled' => '未排程',
    'Schedule overdue' => '排程逾期',
    'Scanning' => '正在扫描',
    'Blocked' => '已阻止',
    'Completed' => '已完成',
    'Stopped' => '已停止',
    'Delete Saved Translations' => '删除已保存的译文',
    'Deleting a saved translation cannot be undone. Review individual records before deleting.' => '删除已保存的译文无法撤销。请先逐条检查，再确认删除。',
    'Review %s translations' => '检查 %s 译文',
    'Page cache refreshed. Saved translations and queue items were preserved.' => '页面缓存已刷新，已保存的译文和队列任务均已保留。',
    'Translation controls updated.' => '翻译控制状态已更新。',
    'Request failed. Refresh the status before retrying.' => '请求失败。请先刷新状态再重试。',
    'This cache action is no longer available. No translations or queue items were deleted.' => '此缓存操作已停用。未删除任何译文或队列任务。',
    'WordPress could not schedule translation. Pause settings were kept.' => 'WordPress 无法排程翻译任务，已保留原暂停设置。',
    'A limited translation sample is still running.' => '小批量翻译测试仍在进行中。',
    'Choose a configured language.' => '请选择已配置的语言。',
    'Enable the multilingual site and configure AI Translation first.' => '请先启用多语言站点并配置 AI 翻译。',
    'Translation is safety-paused. Test the saved AI connection and retry a limited language sample first.' => '翻译处于安全暂停。请先测试已保存的 AI 连接，再重试一个语言的小批量样本。',
    'Content scan scheduled. Translation settings were kept.' => '内容扫描已排程，原有翻译设置保持不变。',
    'SEGMENTS' => '文本段',
];
$translations['zh_CN'] = array_replace( $translations['zh_CN'], $controls_zh );
$strings = array_values( array_unique( array_merge( $strings, array_keys( $controls_zh ) ) ) );
sort( $strings, SORT_STRING );

write_pot( $dir . '/gml-translate.pot', $strings );
foreach ( $translations as $locale => $map ) {
    write_po( $dir . '/gml-translate-' . $locale . '.po', $locale, $strings, $map );
    write_mo( $dir . '/gml-translate-' . $locale . '.mo', $locale, $strings, $map );
}

echo 'Generated ' . count( $translations ) . ' language packs with ' . count( $strings ) . " source strings.\n";

function extract_i18n_strings( $root ) {
    $strings = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
    );
    $pattern = '/\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*,\s*([\'"])gml-translate\3/s';

    foreach ( $iterator as $file ) {
        if ( ! $file->isFile() || $file->getExtension() !== 'php' ) continue;
        $path = $file->getPathname();
        if ( strpos( $path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR ) !== false
            || strpos( $path, DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR ) !== false
            || strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) !== false ) {
            continue;
        }
        $code = file_get_contents( $path );
        if ( $code !== false && preg_match_all( $pattern, $code, $matches ) ) {
            foreach ( $matches[2] as $match ) {
                $strings[] = stripcslashes( $match );
            }
        }
    }
    return array_values( array_unique( $strings ) );
}

function header_text( $locale ) {
    return implode( "\n", [
        'Project-Id-Version: GML Translate 2.11.1-rc.3',
        'Report-Msgid-Bugs-To: https://github.com/hwc0212/gml-translate/issues',
        'POT-Creation-Date: 2026-08-31 00:00+0000',
        'PO-Revision-Date: 2026-08-31 00:00+0000',
        'Language: ' . $locale,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Generator: GML Translate language pack builder',
    ] ) . "\n";
}

function po_escape( $value ) {
    return str_replace( [ "\\", '"', "\n", "\r", "\t" ], [ "\\\\", '\\"', '\\n', '', '\\t' ], $value );
}

function write_pot( $file, array $strings ) {
    $output = po_header( '' );
    foreach ( $strings as $string ) {
        $output .= 'msgid "' . po_escape( $string ) . '"' . "\nmsgstr \"\"\n\n";
    }
    file_put_contents( $file, rtrim( $output ) . "\n" );
}

function write_po( $file, $locale, array $strings, array $map ) {
    $output = po_header( $locale );
    foreach ( $strings as $string ) {
        $output .= 'msgid "' . po_escape( $string ) . '"' . "\n";
        $output .= 'msgstr "' . po_escape( $map[ $string ] ?? $string ) . '"' . "\n\n";
    }
    file_put_contents( $file, rtrim( $output ) . "\n" );
}

function po_header( $locale ) {
    $output = "msgid \"\"\nmsgstr \"\"\n";
    foreach ( explode( "\n", header_text( $locale ) ) as $line ) {
        if ( $line !== '' ) $output .= '"' . po_escape( $line . "\n" ) . '"' . "\n";
    }
    return $output . "\n";
}

function write_mo( $file, $locale, array $strings, array $map ) {
    $entries = [ '' => header_text( $locale ) ];
    foreach ( $strings as $string ) $entries[ $string ] = $map[ $string ] ?? $string;
    ksort( $entries, SORT_STRING );

    $ids = array_keys( $entries );
    $values = array_values( $entries );
    $count = count( $ids );
    $original_offset = 28;
    $translation_offset = $original_offset + $count * 8;
    $strings_offset = $translation_offset + $count * 8;
    $ids_blob = implode( "\0", $ids ) . "\0";
    $values_blob = implode( "\0", $values ) . "\0";

    $offset = $strings_offset;
    $original_table = '';
    foreach ( $ids as $id ) {
        $original_table .= pack( 'VV', strlen( $id ), $offset );
        $offset += strlen( $id ) + 1;
    }
    $offset = $strings_offset + strlen( $ids_blob );
    $translation_table = '';
    foreach ( $values as $value ) {
        $translation_table .= pack( 'VV', strlen( $value ), $offset );
        $offset += strlen( $value ) + 1;
    }

    $header = pack( 'V*', 0x950412de, 0, $count, $original_offset, $translation_offset, 0, 0 );
    file_put_contents( $file, $header . $original_table . $translation_table . $ids_blob . $values_blob );
}
