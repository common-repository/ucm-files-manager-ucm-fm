/**
 * Ukrainian translation
 * @author Oleksandr Palianytsia
 * @version 2015-04-16
 */
(function(root, factory) {
	if (typeof define === 'function' && define.amd) {
		define(['elfinder'], factory);
	} else if (typeof exports !== 'undefined') {
		module.exports = factory(require('elfinder'));
	} else {
		factory(root.elFinder);
	}
}(this, function(elFinder) {
	elFinder.prototype.i18.uk = {
		translator : 'ITLancer',
		language   : 'Українська мова',
		direction  : 'ltr',
		dateFormat : 'd.m.Y H:i',
		fancyDateFormat : '$1 H:i',
		messages   : {

			/********************************** errors **********************************/
			'error'                : 'Помилка',
			'errUnknown'           : 'Невідома помилка.',
			'errUnknownCmd'        : 'Невідома команда.',
			'errJqui'              : 'Неправильне налаштування jQuery UI. Відсутні компоненти: selectable, draggable, droppable.',
			'errNode'              : 'Відсутній елемент DOM для створення elFinder.',
			'errURL'               : 'Неправильне налаштування! Не вказана опція URL.',
			'errAccess'            : 'Доступ заборонено.',
			'errConnect'           : 'Не вдалося з’єднатися з сервером.',
			'errAbort'             : 'З’єднання розірване.',
			'errTimeout'           : 'Тайм-аут з’єднання.',
			'errNotFound'          : 'Не знайдено серверної частини.',
			'errResponse'          : 'Неправильна відповідь від сервера.',
			'errConf'              : 'Неправильне налаштування серверної частини.',
			'errJSON'              : 'Модуль PHP JSON не встановлено.',
			'errNoVolumes'         : 'Немає доступних для читання директорій.',
			'errCmdParams'         : 'Неправильний параметр для команди "$1".',
			'errDataNotJSON'       : 'Дані не у форматі JSON.',
			'errDataEmpty'         : 'Дані відсутні.',
			'errCmdReq'            : 'Серверна частина вимагає назву команди.',
			'errOpen'              : 'Неможливо відкрити "$1".',
			'errNotFolder'         : 'Об’єкт не є папкою.',
			'errNotFile'           : 'Об’єкт не є файлом.',
			'errRead'              : 'Неможливо прочитати "$1".',
			'errWrite'             : 'Неможливо записати в "$1".',
			'errPerm'              : 'Помилка доступу.',
			'errLocked'            : 'Файл "$1" заблоковано - не можливо перемістити, перейменувати чи вилучити.',
			'errExists'            : 'Файл з назвою "$1" вже існує.',
			'errInvName'           : 'Недійсна назва файла.',
			'errFolderNotFound'    : 'Теку не знайдено.',
			'errFileNotFound'      : 'Файл не знайдено.',
			'errTrgFolderNotFound' : 'Цільову теку "$1" не знайдено.',
			'errPopup'             : 'Браузер забороняє відкривати popup-вікно. Дозвольте у налаштування браузера, щоб відкрити файл.',
			'errMkdir'             : 'Неможливо створити теку "$1".',
			'errMkfile'            : 'Неможливо створити файл "$1".',
			'errRename'            : 'Неможливо перейменувати файл "$1".',
			'errCopyFrom'          : 'Копіювання файлів з тому "$1" не дозволено.',
			'errCopyTo'            : 'Копіювання файлів на том "$1" не дозволено.',
			'errUpload'            : 'Помилка відвантаження.',
			'errUploadCommon'      : 'Помилка відвантаження.',
			'errUploadFile'        : 'Неможливо відвантажити файл "$1".',
			'errUploadNoFiles'     : 'Не знайдено файлів для відвантаження.',
			'errMaxSize'           : 'Розмір даних перевищує допустиме значення.',
			'errFileMaxSize'       : 'Розмір файла перевищує допустиме значення.',
			'errUploadMime'        : 'Файли цього типу заборонені.',
			'errUploadTransfer'    : '"$1" : помилка передачі.',
			'errSave'              : 'Неможливо записати "$1".',
			'errCopy'              : 'Неможливо скопіювати "$1".',
			'errMove'              : 'Неможливо перенести "$1".',
			'errCopyInItself'      : 'Неможливо скопіювати "$1" сам у себе.',
			'errRm'                : 'Неможливо вилучити "$1".',
			'errExtract'           : 'Неможливо розпакувати файли з "$1".',
			'errArchive'           : 'Неможливо створити архів.',
			'errArcType'           : 'Тип архіву не підтримується.',
			'errNoArchive'         : 'Файл не є архівом, або є архівом, тип якого не підтримується.',
			'errCmdNoSupport'      : 'Серверна частина не підтримує цієї команди.',
			'errReplByChild'       : 'Папка “$1” не може бути замінена елементом, який вона містить.',
			'errArcSymlinks'       : 'З міркувань безпеки заборонено розпаковувати архіви з символічними посиланнями.',
			'errArcMaxSize'        : 'Розмір файлів архіву перевищує допустиме значення.',
			'errResize'            : 'Неможливо масштабувати "$1".',
			'errUsupportType'      : 'Непідтримуваний тип файла.',
            'errNotUTF8Content'    : 'Файл "$1" не в UTF-8 і не може бути відредагований.',  // added 9.11.2011
            'errNetMount'          : 'Неможливо змонтувати "$1".', // added 17.04.2012
            'errNetMountNoDriver'  : 'Непідтримуваний протокл.',     // added 17.04.2012
            'errNetMountFailed'    : 'В процесі монтування сталася помилка.',         // added 17.04.2012
            'errNetMountHostReq'   : 'Host required.', // added 18.04.2012
            'errSessionExpires'    : 'Час сеансу минув через неактивність.',
            'errCreatingTempDir'   : 'НЕможливо створити тимчасову директорію: "$1"',
            'errFtpDownloadFile'   : 'Неможливо завантажити файл з FTP: "$1"',
            'errFtpUploadFile'     : 'Неможливо завантажити файл на FTP: "$1"',
            'errFtpMkdir'          : 'Неможливо створити віддалений каталог на FTP: "$1"',
            'errArchiveExec'       : 'Помилка при архівації файлів: "$1"',
            'errExtractExec'       : 'Помилка при розархівуванні файлів: "$1"',
            'errNetUnMount'        : 'Неможливо демонтувати', // from v2.1 added 30.04.2012
            'errConvUTF8'          : 'Неможливо конвертувати в UTF - 8', // from v2.1 added 08.04.2014
            'errFolderUpload'      : 'Використовуйте Google Chrome, якщо ви хочете завантажити папку', // from v2.1 added 26.6.2015
            'errSearchTimeout'     : 'Час пошуку "$1" вийшов. Результат пошуку частковий', // from v2.1 added 12.1.2016
            'errReauthRequire'     : 'Необхідна повторна авторизація.', // from v2.1.10 added 3.24.2016

			/******************************* commands names ********************************/
			'cmdarchive'   : 'Архівувати',
			'cmdback'      : 'Назад',
			'cmdcopy'      : 'Копівати',
			'cmdcut'       : 'Вирізати',
			'cmddownload'  : 'Завантажити',
			'cmdduplicate' : 'Дублювати',
			'cmdedit'      : 'Редагувати файл',
			'cmdextract'   : 'Розпакувати файли з архіву',
			'cmdforward'   : 'Вперед',
			'cmdgetfile'   : 'Вибрати файли',
			'cmdhelp'      : 'Про програму',
			'cmdhome'      : 'Додому',
			'cmdinfo'      : 'Інформація',
			'cmdmkdir'     : 'Створити теку',
			'cmdmkfile'    : 'Створити файл',
			'cmdopen'      : 'Відкрити',
			'cmdpaste'     : 'Вставити',
			'cmdquicklook' : 'Попередній перегляд',
			'cmdreload'    : 'Перечитати',
			'cmdrename'    : 'Перейменувати',
			'cmdrm'        : 'Вилучити',
			'cmdsearch'    : 'Шукати файли',
			'cmdup'        : 'На 1 рівень вгору',
			'cmdupload'    : 'Відвантажити файли',
			'cmdview'      : 'Перегляд',
			'cmdresize'    : 'Масштабувати зображення',
			'cmdsort'      : 'Сортування',
            'cmdnetmount'  : 'Змонтувати мережевий диск', // added 18.04.2012
            'cmdnetunmount': 'Розмонтувати', // from v2.1 added 30.04.2012
            'cmdplaces'    : 'To Places', // added 28.12.2014
            'cmdchmod'     : 'Змінити права', // from v2.1 added 20.6.2015
            'cmdopendir'   : 'Відкрии директорію', // from v2.1 added 13.1.2016
			'cmdcloudpermissionpublic'      : 'Public permission',
			'cmdcloudpermissionprivate'      : 'Private permission',

			/*********************************** buttons ***********************************/
			'btnClose'  : 'Закрити',
			'btnSave'   : 'Зберегти',
			'btnRm'     : 'Вилучити',
			'btnApply'  : 'Застосувати',
			'btnCancel' : 'Скасувати',
			'btnNo'     : 'Ні',
			'btnYes'    : 'Так',
			'btnMount'  : 'Підключити',  // added 18.04.2012
			'btnApprove': 'Перейти в $1 і прийняти', // from v2.1 added 26.04.2012
			'btnUnmount': 'Відключити', // from v2.1 added 30.04.2012
			'btnConv'   : 'Конвертувати', // from v2.1 added 08.04.2014
			'btnCwd'    : 'Тут',      // from v2.1 added 22.5.2015
			'btnVolume' : 'Розділ',    // from v2.1 added 22.5.2015
			'btnAll'    : 'Всі',       // from v2.1 added 22.5.2015
			'btnMime'   : 'MIME тип', // from v2.1 added 22.5.2015
			'btnFileName':'Назва файла',  // from v2.1 added 22.5.2015
			'btnSaveClose': 'Зберегти і вийти', // from v2.1 added 12.6.2015
			'btnBackup' : 'Резервна копія', // fromv2.1 added 28.11.2015

			/******************************** notifications ********************************/
			'ntfopen'     : 'Відкрити теку',
			'ntffile'     : 'Відкрити файл',
			'ntfreload'   : 'Перечитати вміст теки',
			'ntfmkdir'    : 'Створення теки',
			'ntfmkfile'   : 'Створення файлів',
			'ntfrm'       : 'Вилучити файли',
			'ntfcopy'     : 'Копіювати файли',
			'ntfmove'     : 'Перенести файли',
			'ntfprepare'  : 'Підготовка до копіювання файлів',
			'ntfrename'   : 'Перейменувати файли',
			'ntfupload'   : 'Відвантажити файли',
			'ntfdownload' : 'Завантажити файли',
			'ntfsave'     : 'Записати файли',
			'ntfarchive'  : 'Створення архіву',
			'ntfextract'  : 'Розпаковування архіву',
			'ntfsearch'   : 'Пошук файлів',
			'ntfsmth'     : 'Виконується >_<',
			'ntfloadimg'  : 'Завантаження зображення',
            'ntfnetmount' : 'Монтування мережевого диска', // added 18.04.2012
            'ntfnetunmount': 'Розмонтування мережевого диска', // from v2.1 added 30.04.2012
            'ntfdim'      : 'Acquiring image dimension', // added 20.05.2013
            'ntfreaddir'  : 'Читання інформації директорії', // from v2.1 added 01.07.2013
            'ntfurl'      : 'отримання URL посилання', // from v2.1 added 11.03.2014
            'ntfchmod'    : 'Зміна прав файлу', // from v2.1 added 20.6.2015
            'ntfpreupload': 'Перевірка імені завантажуваного файла', // from v2.1 added 31.11.2015
            'ntfzipdl'    : 'Створення файлу для завантаження', // from v2.1.7 added 23.1.2016

			/************************************ dates **********************************/
			'dateUnknown' : 'невідомо',
			'Today'       : 'сьогодні',
			'Yesterday'   : 'вчора',
			'Jan'         : 'Січ',
			'Feb'         : 'Лют',
			'Mar'         : 'Бер',
			'Apr'         : 'Кві',
			'May'         : 'Тра',
			'Jun'         : 'Чер',
			'Jul'         : 'Лип',
			'Aug'         : 'Сер',
			'Sep'         : 'Вер',
			'Oct'         : 'Жов',
			'Nov'         : 'Лис',
			'Dec'         : 'Гру',
			'January'     : 'січня',
			'February'    : 'лютого',
			'March'       : 'березня',
			'April'       : 'квітня',
			'May'         : 'травня',
			'June'        : 'червня',
			'July'        : 'липня',
			'August'      : 'серпня',
			'September'   : 'вересня',
			'October'     : 'жовтня',
			'November'    : 'листопада',
			'December'    : 'грудня',
			'Sunday'      : 'Неділя',
			'Monday'      : 'Понеділок',
			'Tuesday'     : 'Вівторок',
			'Wednesday'   : 'Середа',
			'Thursday'    : 'Четвер',
			'Friday'      : 'П’ятниця',
			'Saturday'    : 'Субота',
			'Sun'         : 'Нд',
			'Mon'         : 'Пн',
			'Tue'         : 'Вт',
			'Wed'         : 'Ср',
			'Thu'         : 'Чт',
			'Fri'         : 'Пт',
			'Sat'         : 'Сб',
			/******************************** sort variants ********************************/
			'sortnameDirsFirst' : 'за назвою (теки на початку)',
			'sortkindDirsFirst' : 'за типом (теки на початку)',
			'sortsizeDirsFirst' : 'за розміром (теки на початку)',
			'sortdateDirsFirst' : 'за датою (теки на початку)',
			'sortname'          : 'за назвою',
			'sortkind'          : 'за типом',
			'sortsize'          : 'за розміром',
			'sortdate'          : 'за датою',

			/********************************** messages **********************************/
			'confirmReq'      : 'Підтвердіть',
			'confirmRm'       : 'Ви справді хочете вилучити файли?<br/>Операція незворотня!',
			'confirmRepl'     : 'Замінити старий файл новим?',
			'apllyAll'        : 'Застосувати до всіх',
			'name'            : 'Назва',
			'size'            : 'Розмір',
			'perms'           : 'Доступи',
			'modify'          : 'Змінено',
			'kind'            : 'Тип',
			'read'            : 'читання',
			'write'           : 'запис',
			'noaccess'        : 'недоступно',
			'and'             : 'і',
			'unknown'         : 'невідомо',
			'selectall'       : 'Вибрати всі файли',
			'selectfiles'     : 'Вибрати файл(и)',
			'selectffile'     : 'Вибрати перший файл',
			'selectlfile'     : 'Вибрати останній файл',
			'viewlist'        : 'Списком',
			'viewicons'       : 'Значками',
			'places'          : 'Розташування',
			'calc'            : 'Вирахувати',
			'path'            : 'Шлях',
			'aliasfor'        : 'Аліас для',
			'locked'          : 'Заблоковано',
			'dim'             : 'Розміри',
			'files'           : 'Файли',
			'folders'         : 'теки',
			'items'           : 'Елементи',
			'yes'             : 'так',
			'no'              : 'ні',
			'link'            : 'Посилання',
			'searcresult'     : 'Результати пошуку',
			'selected'        : 'Вибрані елементи',
			'about'           : 'Про',
			'shortcuts'       : 'Ярлики',
			'help'            : 'Допомога',
			'webfm'           : 'Web-менеджер файлів',
			'ver'             : 'Версія',
			'protocol'        : 'версія протоколу',
			'homepage'        : 'Сторінка проекту',
			'docs'            : 'Документація',
			'github'          : 'Fork us on Github',
			'twitter'         : 'Слідкуйте у Твітері',
			'facebook'        : 'Приєднуйтесь у фейсбуці',
			'team'            : 'Автори',
			'chiefdev'        : 'головний розробник',
			'developer'       : 'розробник',
			'contributor'     : 'учасник',
			'maintainer'      : 'супроводжувач',
			'translator'      : 'перекладач',
			'icons'           : 'Значки',
			'dontforget'      : 'і не забудьте рушничок',
			'shortcutsof'     : 'Ярлики заборонені',
			'dropFiles'       : 'Кидайте файли сюди',
			'or'              : 'або',
			'selectForUpload' : 'Виберіть файли для відвантаження',
			'moveFiles'       : 'Перемістити файли',
			'copyFiles'       : 'Копіювати файли',
			'rmFromPlaces'    : 'Вилучити з розташувань',
			'untitled folder' : 'неназвана папка',
			'untitled file.txt' : 'неназваний файл.txt',
			'aspectRatio'     : 'Співвідношення',
			'scale'           : 'Масштаб',
			'width'           : 'Ширина',
			'height'          : 'Висота',
			'mode'            : 'Режим',
			'resize'          : 'Змінити розмір',
			'crop'            : 'Обрізати',
			'rotate'          : 'Повернути',
			'rotate-cw'       : 'Повернути на 90 градусів за год. стр.',
			'rotate-ccw'      : 'Повернути на 90 градусів проти год. стр.',
			'degree'          : 'Градус',

			/********************************** mimetypes **********************************/
			'kindUnknown'     : 'Невідомо',
			'kindFolder'      : 'Папка',
			'kindAlias'       : 'Аліас',
			'kindAliasBroken' : 'Битий аліас',
			// applications
			'kindApp'         : 'Програма',
			'kindPostscript'  : 'Документ Postscript',
			'kindMsOffice'    : 'Документ Microsoft Office',
			'kindMsWord'      : 'Документ Microsoft Word',
			'kindMsExcel'     : 'Документ Microsoft Excel',
			'kindMsPP'        : 'Презентація Microsoft Powerpoint',
			'kindOO'          : 'Документ Open Office',
			'kindAppFlash'    : 'Flash-додаток',
			'kindPDF'         : 'Документ переносного формату (PDF)',
			'kindTorrent'     : 'Файл Bittorrent',
			'kind7z'          : 'Архів 7z archive',
			'kindTAR'         : 'Архів TAR archive',
			'kindGZIP'        : 'Архів GZIP archive',
			'kindBZIP'        : 'Архів BZIP archive',
			'kindZIP'         : 'Архів ZIP archive',
			'kindRAR'         : 'Архів RAR archive',
			'kindJAR'         : 'Файл Java JAR',
			'kindTTF'         : 'Шрифт True Type',
			'kindOTF'         : 'Шрифт Open Type',
			'kindRPM'         : 'Пакунок RPM',
			// texts
			'kindText'        : 'Текстовий документ',
			'kindTextPlain'   : 'Простий текст',
			'kindPHP'         : 'Код PHP',
			'kindCSS'         : 'Каскадна таблиця стилів (CSS)',
			'kindHTML'        : 'Документ HTML',
			'kindJS'          : 'Код Javascript',
			'kindRTF'         : 'Rich Text Format',
			'kindC'           : 'Код C',
			'kindCHeader'     : 'Заголовковий код C',
			'kindCPP'         : 'Код C++',
			'kindCPPHeader'   : 'Заголовковий код C++',
			'kindShell'       : 'Скрипт Unix shell',
			'kindPython'      : 'Код Python',
			'kindJava'        : 'Код Java',
			'kindRuby'        : 'Код Ruby',
			'kindPerl'        : 'Код Perl',
			'kindSQL'         : 'Код SQL',
			'kindXML'         : 'Документ XML',
			'kindAWK'         : 'Код AWK',
			'kindCSV'         : 'Значення розділені комою (CSV)',
			'kindDOCBOOK'     : 'Документ Docbook XML',
			// images
			'kindImage'       : 'Зображення',
			'kindBMP'         : 'Зображення BMP',
			'kindJPEG'        : 'Зображення JPEG',
			'kindGIF'         : 'Зображення GIF',
			'kindPNG'         : 'Зображення PNG',
			'kindTIFF'        : 'Зображення TIFF',
			'kindTGA'         : 'Зображення TGA',
			'kindPSD'         : 'Зображення Adobe Photoshop',
			'kindXBITMAP'     : 'Зображення X bitmap',
			'kindPXM'         : 'Зображення Pixelmator',
			// media
			'kindAudio'       : 'Аудіо',
			'kindAudioMPEG'   : 'Аудіо MPEG',
			'kindAudioMPEG4'  : 'Аудіо MPEG-4',
			'kindAudioMIDI'   : 'Аудіо MIDI',
			'kindAudioOGG'    : 'Аудіо Ogg Vorbis',
			'kindAudioWAV'    : 'Аудіо WAV',
			'AudioPlaylist'   : 'Список відтворення MP3',
			'kindVideo'       : 'Відео',
			'kindVideoDV'     : 'Відео DV movie',
			'kindVideoMPEG'   : 'Відео MPEG movie',
			'kindVideoMPEG4'  : 'Відео MPEG-4 movie',
			'kindVideoAVI'    : 'Відео AVI movie',
			'kindVideoMOV'    : 'Відео Quick Time',
			'kindVideoWM'     : 'Відео Windows Media',
			'kindVideoFlash'  : 'Відео Flash',
			'kindVideoMKV'    : 'Відео Matroska',
			'kindVideoOGG'    : 'Відео Ogg'
		}
	}
}));

