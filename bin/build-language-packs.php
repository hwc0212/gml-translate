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
	'Uninstall Data Retention' => '卸载数据保留',
	'Uninstall data behavior' => '卸载数据处理方式',
	'Uninstall Data' => '卸载数据',
	'Retain all plugin data (recommended)' => '保留全部插件数据（推荐）',
	'Permanently delete all plugin data' => '永久删除全部插件数据',
	'Type DELETE to enable complete removal' => '输入 DELETE 以启用永久删除',
	'Save Uninstall Preference' => '保存卸载设置',
	'WordPress cannot show a plugin-specific choice after you click Delete. Choose here what should happen if the plugin is later deleted. Deactivation and normal updates never delete stored data.' => 'WordPress 在点击删除后无法显示插件专用选择框。请在这里预先选择以后删除插件时如何处理数据。停用插件和普通更新永远不会删除已保存的数据。',
	'Keeps settings, encrypted provider credentials, language configuration, glossary, queue, manual edits, and the translation library for a future reinstall.' => '保留设置、加密的服务商凭据、语言配置、术语表、队列、人工修改和翻译库，重新安装后可继续使用。',
	'When the plugin is deleted, removes translation tables, saved and manual translations, queue history, settings, credentials, glossary, jobs, and cache. This cannot be undone.' => '删除插件时，清理翻译表、已保存和人工译文、队列历史、设置、凭据、术语表、任务和缓存。此操作无法撤销。',
	'GML SEO is also installed, so deleting this product will not remove shared translation data. To remove it later, enable complete removal in the last remaining GML product before deleting that product.' => '检测到 GML SEO，删除当前产品不会清理共享翻译数据。如需以后彻底清理，请在最后保留的 GML 产品中启用永久删除后，再删除该产品。',
	'You are not allowed to change uninstall data retention.' => '你没有权限修改卸载数据保留设置。',
	'Complete removal was not enabled. Type DELETE exactly to confirm.' => '未启用永久删除。请准确输入 DELETE 进行确认。',
	'The uninstall preference could not be saved. No data has been deleted.' => '卸载设置保存失败，未删除任何数据。',
	'Complete removal is armed. No data is deleted now; it runs only when the plugin is deleted from the Plugins page.' => '已启用永久删除。现在不会删除数据；只有从插件页面真正删除插件时才会执行。',
	'The uninstall preference could not be saved. Existing data remains unchanged.' => '卸载设置保存失败，现有数据保持不变。',
	'Plugin data will be retained if GML Translate is deleted.' => '删除 GML Translate 时将保留插件数据。',
	'Language Site Routing' => '语言站点路由',
	'Choose whether each language is translated on this WordPress site or linked to an independent website. External sites are excluded from local routing, crawling, and the AI translation queue. This plugin never writes content or settings to the remote website.' => '选择每种语言由当前 WordPress 站点提供，或链接到独立网站。外部站点不会参与本地路由、扫描和 AI 翻译队列；插件不会向远程网站写入内容或设置。',
	'Language' => '语言',
	'Delivery' => '提供方式',
	'External Site URL' => '外部站点 URL',
	'Page Mapping' => '页面映射',
	'This WordPress site (subdirectory)' => '当前 WordPress 站点（子目录）',
	'External domain or subdomain' => '外部域名或子域名',
	'HTTPS only. Configure the reciprocal language link on the other website.' => '仅支持 HTTPS。请在另一个网站上配置反向语言链接。',
	'Match the current path' => '匹配当前路径',
	'Always open the external homepage' => '始终打开外站首页',
	'Use same path only when equivalent URLs exist on both sites. Homepage mode is omitted from inner-page hreflang.' => '仅当两个站点存在对应 URL 时使用同路径。首页模式不会加入内页 hreflang。',
	'Appearance' => '外观',
	'Seamless (inherit theme)' => '无缝（继承主题）',
	'Outlined' => '描边',
	'Solid' => '实色',
	'Seamless is best inside navigation menus. Outlined and Solid provide a clearer standalone control.' => '导航菜单内建议使用无缝样式；描边和实色更适合作为独立控件。',
	'Dropdown Alignment' => '下拉菜单对齐',
	'Automatic' => '自动',
	'Align left' => '左对齐',
	'Align right' => '右对齐',
	'One external language site is shown in the language switcher and excluded from this AI queue.' => '1 个外部语言站点会显示在语言切换器中，但不会进入此 AI 队列。',
	'%d external language sites are shown in the language switcher and excluded from this AI queue.' => '%d 个外部语言站点会显示在语言切换器中，但不会进入此 AI 队列。',
	'Enter a valid HTTPS URL on a different host for %s. Language routing was not changed.' => '请为 %s 输入其他主机上的有效 HTTPS URL。语言路由未更改。',
	'Language site routing could not be saved. Check database writes and try again; the previous routing remains active.' => '语言站点路由无法保存。请检查数据库写入后重试；原路由仍然有效。',
	'Qwen API Key' => 'Qwen API Key',
	'Qwen Model' => 'Qwen 模型',
	'OpenAI API Key' => 'OpenAI API Key',
	'OpenAI Model' => 'OpenAI 模型',
	'API Base URL' => 'API 基础 URL',
	'Choose the AI engine for translation.' => '选择用于翻译的 AI 服务商。',
	'Get your key from' => '获取 API Key：',
    'Confirm Page Cache Refresh' => '确认刷新页面缓存',
    'Only rendered page cache will be refreshed. Saved translations, manual edits and queue items will not be deleted.' => '仅刷新已生成的页面缓存，不删除已保存的译文、人工修改或翻译队列。',
    'Type REFRESH to confirm' => '输入 REFRESH 确认',
    'Confirm Refresh' => '确认刷新',
    'Type REFRESH to confirm page cache refresh. No changes were made.' => '请输入 REFRESH 确认刷新页面缓存。本次未执行任何修改。',
    'Start All Pending' => '启动全部待翻译任务',
    'Pause All' => '暂停全部',
    'Start Pending for This Language' => '启动该语言的待翻译任务',
    'Pause Sample' => '暂停小样本',
    'Sample paused' => '小样本已暂停',
    'Sample scheduled' => '小样本已排程',
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
    'Resume Sample' => '继续小样本',
    'A limited translation sample is active. Resume that sample before starting other work.' => '已有受限翻译样本，请先继续并完成该样本，再启动其他任务。',
    'Finish Sample' => '结束小样本',
    'Limited sample: %1$s, %2$d of %3$d items remaining.' => '受限小样本：%1$s，共 %3$d 条，剩余 %2$d 条。',
    'Other queue items and content scanning are on hold. The queue pauses automatically when this sample finishes.' => '其他队列任务和内容扫描保持暂停。该小样本完成后，队列会自动暂停。',
    'No valid limited sample is available for an enabled language.' => '已启用的语言中没有有效的受限小样本。',
    'The current translation batch is still finishing. Try again after it stops.' => '当前翻译批次尚未结束，请在该批次停止后再继续。',
    'Request failed. Refresh the status before retrying.' => '请求失败。请先刷新状态再重试。',
    'This cache action is no longer available. No translations or queue items were deleted.' => '此缓存操作已停用。未删除任何译文或队列任务。',
    'WordPress could not schedule translation. Pause settings were kept.' => 'WordPress 无法排程翻译任务，已保留原暂停设置。',
    'A limited translation sample is still running.' => '小批量翻译测试仍在进行中。',
    'Choose a configured language.' => '请选择已配置的语言。',
    'Enable the multilingual site and configure AI Translation first.' => '请先启用多语言站点并配置 AI 翻译。',
    'Translation is safety-paused. Test the saved AI connection and retry a limited language sample first.' => '翻译处于安全暂停。请先测试已保存的 AI 连接，再重试一个语言的小批量样本。',
    'Content scan scheduled. Translation settings were kept.' => '内容扫描已排程，原有翻译设置保持不变。',
    'SEGMENTS' => '文本段',
    'Waiting for provider cooldown' => '等待 AI 服务商冷却',
    'Stored Failed Items' => '已保存的失败记录',
    'The AI provider is cooling down temporarily.' => 'AI 服务商正在临时冷却。',
    'No queue item is being discarded and no retry attempt is consumed. Processing will resume automatically after %s.' => '不会丢弃队列任务，也不会消耗任务重试次数。处理将在 %s 后自动恢复。',
    'Reason: %1$s (%2$s).' => '原因：%1$s（%2$s）。',
    'stored failed items' => '条已保存的失败记录',
    'The current-content inventory is rebuilding. Until it completes, queue controls use legacy totals and public language readiness remains withheld.' => '当前内容清单正在重建。完成前，队列控制继续使用旧版总数，并暂不公开语言就绪状态。',
    'Progress and retries now use current site content. Stored failures from removed or replaced content remain available for audit, but do not reduce current progress or consume retry quota.' => '进度和重试现在只统计当前网站内容。已删除或已替换内容的历史失败记录仍保留供审计，但不会降低当前进度或占用重试额度。',
    'not queued' => '尚未入队',
    'stored history' => '历史记录',
    'Current Failed Items' => '当前失败项',
    'Stored History' => '历史记录',
    'current failed items' => '个当前失败项',
    '%s stored historical records do not affect current progress or retry quota.' => '%s 条历史记录不影响当前进度或重试额度。',
    'Review stored failure history' => '查看已保存的失败历史',
    '%s are new since the last successful connection test.' => '其中 %s 条是在上次连接测试成功后新增的。',
    'These are acknowledged historical records; they do not mean the provider is currently failing.' => '这些是已确认的历史记录，不代表服务商当前仍在失败。',
    'Retry All remains disabled to protect API quota. Retry one language at a time in samples of at most 25 after a successful connection test.' => '为保护 API 额度，仍不提供全部重试。连接测试成功后，请按语言每次最多重试 25 条。',
    'Review the 20 most recent failed items' => '查看最近 20 条失败记录',
    'Legacy rows may show their original queue time when an exact failure time was not stored. Credentials and raw provider responses are never displayed.' => '旧记录未保存准确失败时间时会显示最初入队时间。这里不会显示凭据或服务商原始响应。',
    'Source text' => '源文本',
    'Language' => '语言',
    'Context' => '内容类型',
    'Error' => '错误',
    'Attempts' => '尝试次数',
    'Recorded at' => '记录时间',
    'Unknown' => '未知',
    'Invalid provider request (HTTP 400)' => '服务商请求无效（HTTP 400）',
    'Authentication or permission error' => '身份验证或权限错误',
    'Model or API resource not found' => '找不到模型或 API 资源',
    'Rate limit or quota exceeded' => '达到速率限制或额度上限',
    'Provider temporarily unavailable' => '服务商暂时不可用',
    'Network error' => '网络错误',
    'Provider timeout' => '服务商响应超时',
    'Provider returned no final text' => '服务商未返回最终文本',
    'Provider returned an incomplete response' => '服务商返回了不完整响应',
    'Provider output was truncated' => '服务商输出被截断',
    'Content was blocked by the provider' => '内容被服务商阻止',
    'Empty translation result' => '翻译结果为空',
    'Protected term changed or removed' => '受保护术语被修改或删除',
    'Local translation save failed' => '本地译文保存失败',
    'Source segment exceeds the size limit' => '源文本段超过大小限制',
    'Invalid provider response' => '服务商响应无效',
    'AI provider is not configured' => '尚未配置 AI 服务商',
    'Provider endpoint is not allowed' => '不允许使用该服务商端点',
    'Translation request could not be encoded' => '无法编码翻译请求',
    'Translation request exceeds the safety limit' => '翻译请求超过安全限制',
    'Provider response exceeds the safety limit' => '服务商响应超过安全限制',
    'Provider request failed' => '服务商请求失败',
    'Provider transport failed' => '服务商网络传输失败',
    'Other translation error' => '其他翻译错误',
    'Review' => '审核',
    'Sorry, you are not allowed to review translations.' => '抱歉，你无权审核翻译。',
    'The review database schema is unavailable.' => '翻译审核数据库结构不可用。',
    'Confirm that you reviewed the current translation snapshot.' => '请确认你已审核当前翻译快照。',
    'Add a reason before rejecting this translation.' => '拒绝此翻译前请填写原因。',
    'Unknown review action.' => '未知的审核操作。',
    'The current translation snapshot was approved.' => '当前翻译快照已批准。',
    'The current translation snapshot was rejected and the reason was recorded.' => '当前翻译快照已拒绝，并已记录原因。',
    'This translation is not machine-complete. Finish or repair it before review.' => '此翻译尚未达到机器完整状态，请先完成或修复。',
    'Only configured local target languages can be reviewed.' => '只能审核已配置的本站目标语言。',
    'The review decision could not be saved. No partial decision was kept.' => '无法保存审核决定，未保留任何不完整状态。',
    'The review database schema is not ready. Reload this administration page after the database upgrade completes.' => '翻译审核数据库尚未就绪，请在数据库升级完成后重新加载此后台页面。',
    'Phase 2C review is a shadow workflow. An approval records your decision for the exact current manifest and translation snapshot, but it does not publish, hide, route, index, or advertise a language page yet.' => 'Phase 2C 审核是影子工作流。批准只记录针对当前清单和翻译快照的决定，暂时不会发布、隐藏、路由、索引或推广语言页面。',
    'Human Review' => '人工审核',
    'Review one machine-complete resource and language at a time. Source or translation changes automatically make the old decision stale.' => '每次审核一个已达到机器完整状态的资源和语言。源内容或译文变化后，旧决定会自动失效。',
    '%s resource-language snapshots' => '%s 个资源语言快照',
    'Resource' => '资源',
    'Language' => '语言',
    'Machine Readiness' => '机器就绪度',
    'Coverage' => '覆盖率',
    'Action' => '操作',
    'No current resource manifests are available for local target languages yet.' => '当前还没有可供本站目标语言审核的资源清单。',
    'Back to review queue' => '返回审核队列',
    'Machine' => '机器状态',
    'This decision is stored for the exact manifest and target-language translation generation shown below. It does not publish the page in Phase 2C.' => '此决定只绑定下方显示的精确清单和目标语言译文版本。Phase 2C 不会因此发布页面。',
    'Target language' => '目标语言',
    'Critical missing' => '缺失的关键文本',
    'Manifest generation' => '清单版本',
    'Translation generation' => '译文版本',
    'Open source page' => '打开源页面',
    'Open translated page' => '打开译文页面',
    'Context' => '上下文',
    'Source Text' => '源文本',
    'Translation' => '译文',
    'Status' => '状态',
    'Critical' => '关键',
    'Missing' => '缺失',
    'Record Review Decision' => '记录审核决定',
    'This snapshot is not machine-complete. Approval and rejection remain disabled until its current manifest is complete.' => '此快照尚未达到机器完整状态。在当前清单完整前，批准和拒绝操作保持禁用。',
    'I reviewed this current source and translation snapshot.' => '我已审核当前源文和翻译快照。',
    'Approval note (optional)' => '批准备注（可选）',
    'Approve Current Snapshot' => '批准当前快照',
    'Rejection reason' => '拒绝原因',
    'Reject Current Snapshot' => '拒绝当前快照',
    'Review History' => '审核历史',
    'Decision' => '决定',
    'Reviewer' => '审核人',
    'Recorded at' => '记录时间',
    'Note' => '备注',
    'No review decisions have been recorded.' => '尚未记录审核决定。',
    'User #%d' => '用户 #%d',
    'Complete' => '完整',
    'Incomplete' => '不完整',
    'Approved' => '已批准',
    'Rejected' => '已拒绝',
    'Unreviewed' => '未审核',
    'Stale' => '已失效',
    'Blocked' => '已阻止',
    'Unknown' => '未知',
    'Render Error' => '渲染错误',
    'Rebuilding' => '重建中',
    'Review pages' => '审核分页',
    'The current resource manifest is unavailable.' => '当前资源清单不可用。',
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
        'Project-Id-Version: GML Translate 2.11.1-rc.16',
        'Report-Msgid-Bugs-To: https://github.com/hwc0212/gml-translate/issues',
        'POT-Creation-Date: 2026-09-01 00:00+0000',
        'PO-Revision-Date: 2026-09-01 00:00+0000',
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
