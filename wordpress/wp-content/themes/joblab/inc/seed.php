<?php
/**
 * Наполнение базы демонстрационными данными.
 * Всё созданное помечается мета-полем _jl_demo / jl_demo, чтобы можно было откатить.
 */

defined( 'ABSPATH' ) || exit;

/** Пароль всех демонстрационных аккаунтов. */
const JL_DEMO_PASSWORD = 'joblab123';

function jl_seed_categories() {
	$categories = array(
		'IT и разработка',
		'Менеджмент',
		'Финансы',
		'Логистика',
		'Медицина',
		'Маркетинг',
		'Продажи',
		'Дизайн',
	);

	$map = array();
	foreach ( $categories as $name ) {
		$term = get_term_by( 'name', $name, JL_TAX_CATEGORY );
		if ( ! $term ) {
			$created = wp_insert_term( $name, JL_TAX_CATEGORY );
			if ( is_wp_error( $created ) ) {
				continue;
			}
			$map[ $name ] = (int) $created['term_id'];
			continue;
		}
		$map[ $name ] = (int) $term->term_id;
	}

	return $map;
}

function jl_seed_skills() {
	$skills = array(
		'PHP', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Node.js', 'Python', 'Go',
		'SQL', 'PostgreSQL', 'Docker', 'Kubernetes', 'Linux', 'Git', 'REST API',
		'WordPress', 'Figma', 'UX-исследования', 'Прототипирование', 'Adobe Illustrator',
		'Google Analytics', 'SEO', 'Контекстная реклама', 'Копирайтинг', 'SMM',
		'1С', 'МСФО', 'Бухгалтерский учёт', 'Финансовый анализ', 'Excel',
		'Управление командой', 'Agile', 'Scrum', 'Переговоры', 'Продажи B2B',
		'Логистика ВЭД', 'Складской учёт', 'Водительские права категории C',
		'Английский язык', 'Медсестринское дело', 'Первая помощь',
	);

	$map = array();
	foreach ( $skills as $name ) {
		$term = get_term_by( 'name', $name, JL_TAX_SKILL );
		if ( ! $term ) {
			$created = wp_insert_term( $name, JL_TAX_SKILL );
			if ( is_wp_error( $created ) ) {
				continue;
			}
			$map[ $name ] = (int) $created['term_id'];
			continue;
		}
		$map[ $name ] = (int) $term->term_id;
	}

	return $map;
}

/**
 * Создаёт пользователя, если его ещё нет.
 */
function jl_seed_user( $email, $name, $role ) {
	$user = get_user_by( 'email', $email );
	if ( $user ) {
		return (int) $user->ID;
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => jl_unique_login( $email ),
			'user_email'   => $email,
			'user_pass'    => JL_DEMO_PASSWORD,
			'display_name' => $name,
			'role'         => $role,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		return 0;
	}

	update_user_meta( $user_id, 'jl_demo', 1 );

	return (int) $user_id;
}

function jl_seed_run() {
	$categories = jl_seed_categories();
	$skills     = jl_seed_skills();

	$skill_id = static function ( $name ) use ( $skills ) {
		return isset( $skills[ $name ] ) ? $skills[ $name ] : 0;
	};

	/* --------------------------------------------------------------
	 * Работодатели и компании
	 * ----------------------------------------------------------- */

	// Компании вымышленные, домены — в зарезервированной зоне .example (RFC 2606).
	$employers = array(
		'nimbus'  => array( 'Нимбус Технологии', 'hr@nimbus-tech.example', 'Москва', 'https://nimbus-tech.example', 'Разрабатываем облачные сервисы и внутренние продукты для бизнеса.', 'Москва, ул. Ясеневая, д. 12, бизнес-центр «Ориент», 5 этаж, офис 504' ),
		'arkada'  => array( 'Аркада Банк', 'hr@arkada-bank.example', 'Москва', 'https://arkada-bank.example', 'Универсальный банк: вклады, кредиты и сервисы для предпринимателей.', 'Москва, Кленовый бульвар, д. 3, стр. 2, головной офис, 3 этаж' ),
		'ferrum'  => array( 'Феррум Банк', 'hr@ferrum-bank.example', 'Удалённо', 'https://ferrum-bank.example', 'Онлайн-банк без отделений: вся работа команды идёт удалённо.', 'Москва, Тополиный пер., д. 8, переговорная для гостей, 1 этаж' ),
		'bereg'   => array( 'Берег Маркет', 'hr@bereg-market.example', 'Санкт-Петербург', 'https://bereg-market.example', 'Торговая площадка с собственными складами и доставкой.', 'Санкт-Петербург, Речной проспект, д. 47, лит. А, офис 12' ),
		'vitalis' => array( 'Медцентр «Виталис»', 'hr@vitalis-clinic.example', 'Казань', 'https://vitalis-clinic.example', 'Сеть диагностических лабораторий и амбулаторных клиник.', 'Казань, ул. Липовая, д. 25, клиника «Виталис», 1 этаж, администратор' ),
	);

	$company_ids  = array();
	$employer_ids = array();

	foreach ( $employers as $key => $data ) {
		list( $name, $email, $city, $site, $about, $address ) = $data;

		$user_id = jl_seed_user( $email, $name . ' — HR', JL_ROLE_EMPLOYER );
		if ( ! $user_id ) {
			continue;
		}

		$employer_ids[ $key ] = $user_id;

		$company_id = jl_save_company(
			$user_id,
			array( 'name' => $name, 'city' => $city, 'website' => $site, 'about' => $about, 'address' => $address )
		);

		if ( $company_id ) {
			update_post_meta( $company_id, '_jl_demo', 1 );
			$company_ids[ $key ] = $company_id;
		}
	}

	/* --------------------------------------------------------------
	 * Вакансии
	 * ----------------------------------------------------------- */

	$vacancies = array(
		array(
			'employer'    => 'nimbus',
			'title'       => 'Senior Frontend Developer',
			'meeting'     => 'office',
			'category'    => 'IT и разработка',
			'city'        => 'Москва',
			'salary'      => array( 220000, 280000 ),
			'employment'  => 'full',
			'experience'  => '3-6',
			'skills'      => array( 'JavaScript', 'TypeScript', 'React', 'Git', 'REST API' ),
			'description' => 'Развиваем интерфейсы сервисов с многомиллионной аудиторией. Нужен опыт коммерческой разработки на React и внимание к производительности.',
		),
		array(
			'employer'    => 'nimbus',
			'title'       => 'Backend-разработчик Python',
			'meeting'     => 'call',
			'category'    => 'IT и разработка',
			'city'        => 'Москва',
			'salary'      => array( 200000, 260000 ),
			'employment'  => 'full',
			'experience'  => '3-6',
			'skills'      => array( 'Python', 'PostgreSQL', 'Docker', 'Linux', 'REST API' ),
			'description' => 'Проектируем и поддерживаем высоконагруженные сервисы. Ждём уверенного знания Python, SQL и инфраструктуры в контейнерах.',
		),
		array(
			'employer'    => 'arkada',
			'title'       => 'Product Manager',
			'meeting'     => 'office',
			'category'    => 'Менеджмент',
			'city'        => 'Москва',
			'salary'      => array( 250000, 300000 ),
			'employment'  => 'full',
			'experience'  => '3-6',
			'skills'      => array( 'Управление командой', 'Agile', 'Scrum', 'Переговоры', 'Google Analytics' ),
			'description' => 'Отвечаете за продуктовую стратегию направления, работаете с аналитикой и командой разработки.',
		),
		array(
			'employer'    => 'arkada',
			'title'       => 'Финансовый аналитик',
			'meeting'     => 'office',
			'category'    => 'Финансы',
			'city'        => 'Москва',
			'salary'      => array( 150000, 190000 ),
			'employment'  => 'full',
			'experience'  => '1-3',
			'skills'      => array( 'Финансовый анализ', 'Excel', 'МСФО', '1С' ),
			'description' => 'Готовите управленческую отчётность, строите финансовые модели и защищаете их перед бизнесом.',
		),
		array(
			'employer'    => 'ferrum',
			'title'       => 'UX/UI Designer',
			'meeting'     => 'call',
			'category'    => 'Дизайн',
			'city'        => 'Удалённо',
			'salary'      => array( 160000, 200000 ),
			'employment'  => 'remote',
			'experience'  => '1-3',
			'skills'      => array( 'Figma', 'Прототипирование', 'UX-исследования' ),
			'description' => 'Проектируете интерфейсы мобильного банка: от исследования до готовых макетов и дизайн-системы.',
		),
		array(
			'employer'    => 'ferrum',
			'title'       => 'DevOps-инженер',
			'meeting'     => 'call',
			'category'    => 'IT и разработка',
			'city'        => 'Удалённо',
			'salary'      => array( 240000, 320000 ),
			'employment'  => 'remote',
			'experience'  => '3-6',
			'skills'      => array( 'Kubernetes', 'Docker', 'Linux', 'Go', 'Git' ),
			'description' => 'Отвечаете за CI/CD, наблюдаемость и надёжность платформы. Приветствуется опыт написания операторов на Go.',
		),
		array(
			'employer'    => 'bereg',
			'title'       => 'Менеджер по логистике',
			'meeting'     => 'office',
			'category'    => 'Логистика',
			'city'        => 'Санкт-Петербург',
			'salary'      => array( 110000, 140000 ),
			'employment'  => 'full',
			'experience'  => '1-3',
			'skills'      => array( 'Логистика ВЭД', 'Складской учёт', 'Excel', 'Переговоры' ),
			'description' => 'Планируете маршруты и загрузку складов, контролируете сроки поставок и работаете с подрядчиками.',
		),
		array(
			'employer'    => 'bereg',
			'title'       => 'Менеджер по продажам B2B',
			'category'    => 'Продажи',
			'city'        => 'Санкт-Петербург',
			'salary'      => array( 90000, 180000 ),
			'employment'  => 'full',
			'experience'  => '1-3',
			'skills'      => array( 'Продажи B2B', 'Переговоры', 'Excel' ),
			'description' => 'Привлекаете продавцов на маркетплейс, ведёте переговоры и сопровождаете сделки до подписания.',
		),
		array(
			'employer'    => 'bereg',
			'title'       => 'Интернет-маркетолог',
			'meeting'     => 'call',
			'category'    => 'Маркетинг',
			'city'        => 'Удалённо',
			'salary'      => array( 120000, 160000 ),
			'employment'  => 'flex',
			'experience'  => '1-3',
			'skills'      => array( 'Контекстная реклама', 'Google Analytics', 'SEO', 'SMM', 'Копирайтинг' ),
			'description' => 'Ведёте платный трафик и аналитику кампаний, отвечаете за стоимость привлечения клиента.',
		),
		array(
			'employer'    => 'vitalis',
			'title'       => 'Медицинская сестра',
			'meeting'     => 'office',
			'category'    => 'Медицина',
			'city'        => 'Казань',
			'salary'      => array( 70000, 95000 ),
			'employment'  => 'shift',
			'experience'  => '1-3',
			'skills'      => array( 'Медсестринское дело', 'Первая помощь' ),
			'description' => 'Забор биоматериала, работа с пациентами и ведение медицинской документации. График 2/2.',
		),
		array(
			'employer'    => 'vitalis',
			'title'       => 'Администратор клиники',
			'category'    => 'Менеджмент',
			'city'        => 'Казань',
			'salary'      => array( 55000, 70000 ),
			'employment'  => 'shift',
			'experience'  => 'none',
			'skills'      => array( 'Переговоры', '1С', 'Excel' ),
			'description' => 'Встречаете пациентов, ведёте запись и кассу. Опыт не обязателен — обучаем на месте.',
		),
		array(
			'employer'    => 'nimbus',
			'title'       => 'Срочно! Работа на дому 5000р в день без опыта',
			'category'    => 'Продажи',
			'city'        => 'Удалённо',
			'salary'      => array( 150000, 0 ),
			'employment'  => 'remote',
			'experience'  => 'none',
			'skills'      => array(),
			'description' => 'Пиши в мессенджер, предоплата обязательна. (Демонстрация заблокированной модератором заявки.)',
			'banned'      => true,
		),
	);

	$vacancy_ids = array();

	foreach ( $vacancies as $index => $item ) {
		$author = $employer_ids[ $item['employer'] ] ?? 0;
		if ( ! $author ) {
			continue;
		}

		// Не плодим дубликаты при повторном запуске.
		$existing = get_posts(
			array(
				'post_type'      => JL_CPT_VACANCY,
				'post_status'    => 'any',
				'title'          => $item['title'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $existing ) {
			// Вакансия создана прошлым запуском, до появления формата встречи: дозаполняем.
			if ( ! empty( $item['meeting'] ) && '' === (string) get_post_meta( $existing[0], '_jl_meeting', true ) ) {
				update_post_meta( $existing[0], '_jl_meeting', $item['meeting'] );
			}
			$vacancy_ids[] = (int) $existing[0];
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'     => JL_CPT_VACANCY,
				'post_title'    => $item['title'],
				'post_content'  => $item['description'],
				'post_status'   => 'publish',
				'post_author'   => $author,
				// Разносим даты, чтобы «Свежие вакансии» выглядели живыми.
				'post_date'     => wp_date( 'Y-m-d H:i:s', time() - $index * 7 * HOUR_IN_SECONDS ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - $index * 7 * HOUR_IN_SECONDS ),
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, '_jl_demo', 1 );
		update_post_meta( $post_id, '_jl_company_id', $company_ids[ $item['employer'] ] ?? 0 );
		update_post_meta( $post_id, '_jl_city', $item['city'] );
		update_post_meta( $post_id, '_jl_salary_min', $item['salary'][0] );
		update_post_meta( $post_id, '_jl_salary_max', $item['salary'][1] );
		update_post_meta( $post_id, '_jl_employment', $item['employment'] );
		update_post_meta( $post_id, '_jl_experience', $item['experience'] );
		update_post_meta( $post_id, '_jl_contact', $employers[ $item['employer'] ][1] );
		update_post_meta( $post_id, '_jl_meeting', $item['meeting'] ?? '' );

		if ( isset( $categories[ $item['category'] ] ) ) {
			wp_set_object_terms( $post_id, array( $categories[ $item['category'] ] ), JL_TAX_CATEGORY );
		}

		$skill_ids = array_filter( array_map( $skill_id, $item['skills'] ) );
		if ( $skill_ids ) {
			wp_set_object_terms( $post_id, array_values( $skill_ids ), JL_TAX_SKILL );
		}

		if ( ! empty( $item['banned'] ) ) {
			jl_ban_vacancy( $post_id, 'Не соответствует правилам площадки: недостоверные условия' );
		}

		$vacancy_ids[] = (int) $post_id;
	}

	/* --------------------------------------------------------------
	 * Работники
	 * ----------------------------------------------------------- */

	$seekers = array(
		array(
			'email'      => 'anna.frontend@joblab.example',
			'first'      => 'Анна',
			'last'       => 'Ковалёва',
			'city'       => 'Москва',
			'position'   => 'Frontend-разработчик',
			'salary'     => 240000,
			'experience' => '3-6',
			'education'  => array( 'higher', 'Политехнический институт, программная инженерия' ),
			'skills'     => array( 'JavaScript', 'TypeScript', 'React', 'Git', 'REST API', 'Figma' ),
			'about'      => 'Шесть лет делаю интерфейсы, последние три — на React и TypeScript. Люблю доводить производительность до нормальных цифр и писать тесты.',
		),
		array(
			'email'      => 'dmitry.backend@joblab.example',
			'first'      => 'Дмитрий',
			'last'       => 'Соколов',
			'city'       => 'Москва',
			'position'   => 'Python-разработчик',
			'salary'     => 230000,
			'experience' => '3-6',
			'education'  => array( 'master', 'Университет прикладных наук, прикладная математика' ),
			'skills'     => array( 'Python', 'PostgreSQL', 'Docker', 'Linux', 'SQL', 'REST API' ),
			'about'      => 'Проектирую бэкенды под нагрузку: очереди, кеши, миграции без простоя. Есть опыт перевода монолита на сервисы.',
		),
		array(
			'email'      => 'irina.design@joblab.example',
			'first'      => 'Ирина',
			'last'       => 'Морозова',
			'city'       => 'Удалённо',
			'position'   => 'Продуктовый дизайнер',
			'salary'     => 180000,
			'experience' => '1-3',
			'education'  => array( 'higher', 'Высшая школа дизайна и коммуникаций' ),
			'skills'     => array( 'Figma', 'Прототипирование', 'UX-исследования', 'Adobe Illustrator' ),
			'about'      => 'Работаю от исследования до готовой дизайн-системы. Провела больше сотни интервью с пользователями финансовых продуктов.',
		),
		array(
			'email'      => 'pavel.devops@joblab.example',
			'first'      => 'Павел',
			'last'       => 'Ерофеев',
			'city'       => 'Удалённо',
			'position'   => 'DevOps-инженер',
			'salary'     => 300000,
			'experience' => '6+',
			'education'  => array( 'higher', 'Государственный технический университет, информационные системы' ),
			'skills'     => array( 'Kubernetes', 'Docker', 'Linux', 'Go', 'Git', 'PostgreSQL' ),
			'about'      => 'Восемь лет в эксплуатации. Строил CI/CD с нуля в двух компаниях, довожу время выкатки до минут.',
		),
		array(
			'email'      => 'olga.finance@joblab.example',
			'first'      => 'Ольга',
			'last'       => 'Никитина',
			'city'       => 'Москва',
			'position'   => 'Финансовый аналитик',
			'salary'     => 170000,
			'experience' => '1-3',
			'education'  => array( 'higher', 'Финансовая академия, экономика предприятия' ),
			'skills'     => array( 'Финансовый анализ', 'Excel', 'МСФО', 'Бухгалтерский учёт', '1С' ),
			'about'      => 'Считаю юнит-экономику и собираю управленческую отчётность. Знаю МСФО и умею объяснять цифры не финансистам.',
		),
		array(
			'email'      => 'maxim.sales@joblab.example',
			'first'      => 'Максим',
			'last'       => 'Гордеев',
			'city'       => 'Санкт-Петербург',
			'position'   => 'Менеджер по продажам',
			'salary'     => 150000,
			'experience' => '3-6',
			'education'  => array( 'vocational', 'Торгово-экономический колледж' ),
			'skills'     => array( 'Продажи B2B', 'Переговоры', 'Excel', 'Английский язык' ),
			'about'      => 'Пять лет в B2B-продажах, средний чек сделки — 2 млн рублей. Веду клиента от первого звонка до отгрузки.',
		),
		array(
			'email'      => 'elena.marketing@joblab.example',
			'first'      => 'Елена',
			'last'       => 'Белова',
			'city'       => 'Удалённо',
			'position'   => 'Интернет-маркетолог',
			'salary'     => 140000,
			'experience' => '1-3',
			'education'  => array( 'higher', 'Институт коммуникаций, реклама и связи с общественностью' ),
			'skills'     => array( 'Контекстная реклама', 'Google Analytics', 'SEO', 'SMM', 'Копирайтинг' ),
			'about'      => 'Веду платный трафик и контент одновременно, поэтому хорошо понимаю, какая связка объявления и посадочной работает.',
		),
		array(
			'email'      => 'svetlana.med@joblab.example',
			'first'      => 'Светлана',
			'last'       => 'Титова',
			'city'       => 'Казань',
			'position'   => 'Медицинская сестра',
			'salary'     => 85000,
			'experience' => '3-6',
			'education'  => array( 'vocational', 'Медицинский колледж' ),
			'skills'     => array( 'Медсестринское дело', 'Первая помощь' ),
			'about'      => 'Семь лет в процедурном кабинете. Спокойно работаю со сложными венами и тревожными пациентами.',
		),
		array(
			'email'      => 'roman.logistics@joblab.example',
			'first'      => 'Роман',
			'last'       => 'Жуков',
			'city'       => 'Санкт-Петербург',
			'position'   => 'Логист',
			'salary'     => 120000,
			'experience' => '3-6',
			'education'  => array( 'higher', 'Морская академия, управление перевозками' ),
			'skills'     => array( 'Логистика ВЭД', 'Складской учёт', 'Excel', 'Водительские права категории C' ),
			'about'      => 'Занимаюсь международными перевозками и таможенным оформлением. Знаю Инкотермс и умею спорить с перевозчиками.',
		),
		array(
			'email'      => 'banned.user@joblab.example',
			'first'      => 'Виктор',
			'last'       => 'Спамов',
			'city'       => 'Москва',
			'position'   => 'Менеджер',
			'salary'     => 500000,
			'experience' => 'none',
			'education'  => array( 'secondary', '' ),
			'skills'     => array( 'Переговоры' ),
			'about'      => 'Демонстрационный аккаунт: заблокирован модератором.',
			'banned'     => true,
		),
	);

	$seeker_ids = array();

	foreach ( $seekers as $item ) {
		$user_id = jl_seed_user( $item['email'], $item['first'] . ' ' . $item['last'], JL_ROLE_SEEKER );
		if ( ! $user_id ) {
			continue;
		}

		jl_save_profile(
			$user_id,
			array(
				'jl_first_name'      => $item['first'],
				'jl_last_name'       => $item['last'],
				'jl_phone'           => '+7 900 000-00-00',
				'jl_city'            => $item['city'],
				'jl_position'        => $item['position'],
				'jl_salary'          => $item['salary'],
				'jl_experience'      => $item['experience'],
				'jl_education_level' => $item['education'][0],
				'jl_education_place' => $item['education'][1],
				'jl_about'           => $item['about'],
				'jl_open_to_work'    => 1,
			)
		);

		jl_set_user_skills( $user_id, array_filter( array_map( $skill_id, $item['skills'] ) ) );

		if ( ! empty( $item['banned'] ) ) {
			jl_ban_user( $user_id, 'Демонстрация блокировки: спам в откликах' );
		}

		$seeker_ids[] = $user_id;
	}

	/* --------------------------------------------------------------
	 * Отклики
	 * ----------------------------------------------------------- */

	$applications = array(
		array( 'anna.frontend@joblab.example', 'Senior Frontend Developer', 'Шесть лет на React, последние два — в финтехе. Готова обсудить задачи и показать код.', 'invited' ),
		array( 'dmitry.backend@joblab.example', 'Backend-разработчик Python', 'Работал с похожей нагрузкой, есть опыт миграции на PostgreSQL без простоя.', 'invited' ),
		array( 'pavel.devops@joblab.example', 'DevOps-инженер', 'Поднимал кластеры Kubernetes в проде, писал операторы на Go. Удалёнка мне подходит.', 'viewed' ),
		array( 'irina.design@joblab.example', 'UX/UI Designer', 'Портфолио с мобильным банком приложу отдельно — покажу, как проектировала онбординг.', 'invited' ),
		array( 'olga.finance@joblab.example', 'Финансовый аналитик', 'Есть опыт подготовки отчётности по МСФО и защиты моделей перед инвесткомитетом.' ),
		array( 'maxim.sales@joblab.example', 'Менеджер по продажам B2B', 'Пять лет в B2B, приведу свою базу контактов в сегменте оптовых поставщиков.', 'invited' ),
		array( 'elena.marketing@joblab.example', 'Интернет-маркетолог', 'Снижала стоимость привлечения на 40% за квартал, готова показать кейс.' ),
		array( 'svetlana.med@joblab.example', 'Медицинская сестра', 'График 2/2 удобен, живу в десяти минутах от клиники.', 'invited' ),
		array( 'roman.logistics@joblab.example', 'Менеджер по логистике', 'Занимался ВЭД и складом одновременно, знаком с вашей схемой доставки.', 'rejected' ),
		array( 'anna.frontend@joblab.example', 'UX/UI Designer', 'Помимо фронтенда веду макеты в Figma — рассматриваю смежную роль.' ),
	);

	foreach ( $applications as $item ) {
		list( $email, $vacancy_title, $message ) = $item;
		$state = $item[3] ?? '';

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			continue;
		}

		$vacancy = get_posts(
			array(
				'post_type'      => JL_CPT_VACANCY,
				'post_status'    => 'publish',
				'title'          => $vacancy_title,
				'posts_per_page' => 1,
			)
		);
		if ( ! $vacancy ) {
			continue;
		}

		$result = jl_create_application( $user->ID, $vacancy[0]->ID, $message );
		if ( ! is_wp_error( $result ) ) {
			update_post_meta( $result, '_jl_demo', 1 );
		}

		// Отклик мог появиться раньше: тогда трогаем его статус, только пока никто не менял его с «нового».
		$application = is_wp_error( $result ) ? jl_find_application( $user->ID, $vacancy[0]->ID ) : get_post( $result );
		if ( $state && $application && 'new' === get_post_meta( $application->ID, '_jl_state', true ) ) {
			update_post_meta( $application->ID, '_jl_state', $state );
		}
	}

	update_option( 'jl_seeded', time() );
}

/**
 * Удаляет всё, что создал сидер, вместе с пользовательскими вакансиями.
 */
function jl_seed_reset() {
	$posts = get_posts(
		array(
			'post_type'      => array( JL_CPT_VACANCY, JL_CPT_COMPANY, JL_CPT_APPLICATION ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}

	$users = get_users(
		array(
			'fields'     => 'ID',
			'number'     => 500,
			'meta_key'   => 'jl_demo',
			'meta_value' => 1,
		)
	);

	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	foreach ( $users as $user_id ) {
		wp_delete_user( (int) $user_id );
	}

	delete_option( 'jl_seeded' );
}
