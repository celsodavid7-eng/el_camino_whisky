-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-11-2025 a las 21:39:56
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `el_camino_whisky`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alliances`
--

CREATE TABLE `alliances` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alliances`
--

INSERT INTO `alliances` (`id`, `name`, `description`, `website`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Toku Importados', 'Toku shopping paris', 'https://www.toku.com.py/', 'alliance_1763406791.png', 1, '2025-11-17 19:13:11', '2025-11-17 19:13:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Single Malt', 'Whiskies destilados únicamente de cebada malteada en una sola destilería', '2025-11-13 00:32:34'),
(2, 'Blended', 'Mezclas que combinan whisky de cebada con whisky de otros granos', '2025-11-13 00:32:34'),
(3, 'Bourbon', 'Whisky americano con al menos 51% de maíz', '2025-11-13 00:32:34'),
(4, 'Sherry Cask', 'Whiskies añejados en barricas de Jerez', '2025-11-13 00:32:34'),
(5, 'Port Cask', 'Whiskies con acabado en barricas de Oporto', '2025-11-13 00:32:34'),
(6, 'Speyside', 'Whiskies de la región de Speyside en Escocia', '2025-11-13 00:32:34'),
(7, 'Islay', 'Whiskies de la isla de Islay, conocidos por su carácter ahumado', '2025-11-13 00:32:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chapters`
--

CREATE TABLE `chapters` (
  `id` int(11) NOT NULL,
  `season_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `content` text NOT NULL,
  `chapter_number` int(11) NOT NULL,
  `is_free` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_published` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `duration` varchar(50) DEFAULT 'No especificada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `chapters`
--

INSERT INTO `chapters` (`id`, `season_id`, `title`, `subtitle`, `content`, `chapter_number`, `is_free`, `is_active`, `is_published`, `display_order`, `published_at`, `created_by`, `created_at`, `updated_at`, `duration`) VALUES
(4, 1, 'Introducción', '', 'Primero que nada, quiero darte la bienvenida a este camino: un proceso simple, sencillo y, sobre todo, disfrutable. Desde ahora ya no vas a preocuparte por qué botella comprar ni dónde conseguirla. Todo ese trabajo ya lo hice por vos. Hoy solo te toca sentarte, relajarte y disfrutar de lo que cada una de estas botellas tiene para ofrecerte.\r\n\r\nHay que empezar por el principio: el mundo del whisky tiene varias divisiones. No vamos a meternos de lleno en eso todavía —ese tema lo abordaremos con más detalle en la segunda temporada—. Por ahora, vamos a enfocarnos en dos grandes familias: los *Single Malt* (whiskys destilados únicamente de cebada y en una sola destilería) y los *Blended* (mezclas que combinan whisky de cebada con whisky de otros granos como maíz, centeno o trigo).\r\n\r\nLos principales productores y exportadores de whisky son: Escocia, Irlanda, Japón, Estados Unidos, Canadá e India. En los últimos años también aparecieron nuevos países con expresiones interesantes, como México y Perú. Y, por supuesto, Paraguay —que ya tiene su propio whisky— se llama OLD COTTAGE, y lo produce el grupo de la caña Aristócrata.\r\n\r\n¿Por qué 12 botellas?\r\nMuy sencillo: con esta temática podés tomarte un año entero para aprender todo lo necesario. Vas comprando sin prisa, una botella por mes. Eso sí, no te recomiendo beberla por completo en ese tiempo. Si lo hacés, te estarías perdiendo de una parte esencial: la cata comparativa.\r\n\r\nMi recomendación es que avances despacio con cada botella, que te tomes el tiempo de conocerla, que puedan “conversar”. Así, cuando llegue el siguiente mes con la nueva botella, vas a poder sentarte con ambas y compararlas. Este ejercicio, tan simple como efectivo, agudizará tus sentidos y te dará un conocimiento práctico y real para distinguir una de otra.\r\n\r\nLa copa ideal\r\nCuando llegues a este punto, te recomiendo hacer tus catas con una copa de degustación. Ese es el nombre clave si querés buscarla en cualquier tienda de cristalería. En Ciudad del Este, por ejemplo, podés encontrarlas en Salemma o Choice, en el Shopping del Lago. Más abajo te dejo una imagen de referencia para que te resulte más fácil encontrar una copa similar.\r\n\r\nEjercicio de cata: paso a paso\r\n\r\nServí el whisky. Verté entre 20 y 30 ml (no hace falta medir con precisión). Bastará con contar 2 o 3 segundos mientras servís lentamente.\r\n\r\nDejalo respirar. Una vez en la copa, dejalo reposar unos 5 minutos. Luego levantá la copa y observá el color. Movela suavemente en círculos para ver cómo caen las lágrimas: ¿rápido o lento?, ¿finas o gruesas? No hace falta extenderse más de 1 o 2 minutos, pero sí es importante apreciar con atención.\r\n\r\nPasamos a la nariz. No metas la nariz de golpe. Acercá lentamente la copa y dejá que el aroma te llegue poco a poco. No te apures en buscar notas precisas; lo importante es aprender a detectar diferencias. Con el tiempo, vas a notar cómo tu olfato se vuelve más fino. Podés mover la copa horizontalmente y alternar las fosas nasales (sí, cada una percibe distinto). Cuando la tengas quieta frente a la nariz, inhalá suavemente con la boca ligeramente abierta. Así el alcohol no golpea tan fuerte y podés percibir mejor los aromas. En las primeras botellas el alcohol no será muy marcado, pero vale la pena acostumbrarse desde ahora a hacerlo con cuidado.\r\n\r\nAhora, el gusto. Tomá un pequeño sorbo, solo un pequeño sorbo. Mové el whisky por toda la boca —arriba, abajo, a los lados— y dejalo reposar sobre la lengua unos 8 segundos. Ahí ocurre la magia: vas a notar cómo empezás a salivar y cómo la saliva se mezcla con el whisky. Prestá atención a las sensaciones: ¿es aceitoso?, ¿sedoso?, ¿cálido o más bien fresco? Relajate y dejá que el whisky hable. Luego, tragá suavemente.\r\n\r\nRetrogusto o final. Al tragar, vas a sentir la nota alcohólica. En las primeras botellas será leve, pero irá creciendo a medida que avances. Prestá atención a cuánto dura esa sensación: ¿pasa rápido, se mantiene o se queda largo tiempo?\r\n\r\nY eso es todo. Ahora estás más que listo/a para hacer tus propias catas en la comodidad de tu casa —ya sea en la sala, el comedor, el balcón o la terraza—. Solo un último consejo: elegí un momento y lugar tranquilo, sin olores fuertes ni distracciones, para que el whisky pueda hablarte en toda su expresión.', 0, 1, 1, 1, 0, NULL, 1, '2025-11-17 19:32:47', '2025-11-18 13:41:06', 'No especificada'),
(6, 1, 'Capitulo 1 - Glen Moray Port Cask Finish', 'La apertura de un viaje', 'La Apertura de un Viaje\r\nCapítulo 1: Glen Moray Port Cask Finish\r\n\r\nEsta es la primera botella de nuestro camino del whisky. No la elegí al azar; la elegí por su frescura y la facilidad enorme de beberla. Este es un whisky ideal para quienes quieren dar el primer gran paso con confianza.\r\n\r\nEsta destilería de Speyside, Glen Moray (fundada en 1897 a orillas del río Lossie), es un lugar donde la tradición se mezcla con la experimentación. En lugar de limitarse a barricas aburridas, ellos exploran el acabado (finish) en otras barricas que contenian diferentes tipos de vino, ¡y este acabado en Oporto (Porto Cask) es la prueba de su genialidad!\r\nSi bien este whisky NO declara edad, se estima que permanece al menos 8 años en barricas de ex bourbon para después ser finalizadas en barricas de Oporto de 8 a 12 meses, el tiempo suficiente, para aportar la dulzura suficiente SIN opacar por completo el trabajo previo de las barricas ex-bourbon.\r\n\r\nLa Cata: Un Vino de Oporto en tu Vaso\r\nEste Single Malt rompe el molde al finalizar su maduración en barricas de Oporto, lo que le transfiere una personalidad frutal y vinosca que seduce desde el primer encuentro.\r\n\r\nLo que Vemos\r\nEl color es un ámbar profundo, con destellos rojizos que casi parecen vino tinto en el vaso. Fíjate en las lágrimas: caen lentamente, de cuerpo medio, anunciando una textura interesante en boca.\r\n\r\nEn nariz\r\n¡Respira! Aquí está la magia. La nariz es un estallido de dulzura profunda que te recuerda a la repostería fina. Dominan las notas a cereza marrasquino y ciruela pasa, como si acabaras de abrir un paquete de frutos secos premium. Debajo hay un toque de miel tostada y un leve recuerdo a nuez. Es un aroma dulce, atractivo y complejo: ¡huele exactamente a algo que quieres beber!\r\n\r\nEn boca\r\nLo fascinante es su perfecta sintonía con la nariz: ¡sabe justo a lo que huele, y eso genera una confianza inmediata! En boca, la dulzura está perfectamente equilibrada por una sutil acidez vínica. Notas a chocolate con leche y esa rica uva pasa que el Oporto le imprime. La textura es redonda, casi oleosa.\r\n\r\nRetrogusto/final \r\nEl final es limpio, corto y seco. Con ese post-gusto que te deja un buen vino tinto en el paladar. Termina con una nota de canela, invitándote a seguir con el viaje.', 1, 1, 1, 1, 1, NULL, 1, '2025-11-17 19:37:58', '2025-11-18 13:41:13', 'No especificada'),
(7, 1, 'Capitulo 2 - Glen Moray Sherry Finish', '', 'Capítulo 2: Glen Moray Sherry Finish\r\n\r\nEn esta segunda botella nos sumergimos de vuelta en la destilería Glen Moray, la cual vamos a visitar bastante, ya que es una destilería muy rica en estilos y en calidad.\r\n\r\nLas barricas de Jerez acompañan al whisky desde tiempos inmemoriales. Fue una de las primeras barricas que tuvieron a mano los escoceses para añejar sus whiskys una vez terminaba la destilación. Incluso al día de hoy, hay destilerías que solo operan con este tipo de barricas, ya que el Jerez les aporta una potencia y a la vez una dulzura únicas al whisky.\r\n\r\nComo en el caso anterior, este whisky NO declara edad, y tiene prácticamente el mismo proceso que el Port Cask: permanece al menos 8 años en barricas de ex-bourbon para después ser finalizado en barricas de Jerez Oloroso de 8 a 12 meses. Este tiempo de finish es el suficiente para brindarle más cuerpo y un aroma mucho más profundo.\r\n\r\nLa Cata: La Riqueza Cálida del Jerez\r\nEste Single Malt utiliza el finish de Jerez para envolver la dulzura inicial del bourbon en notas profundas, lo que le transfiere una personalidad especiada, a nuez y sumamente reconfortante.\r\n\r\nLo que Vemos\r\nEl color es un oro intenso y profundo, casi un caoba rojizo, resultado de la fuerte influencia del Vino de Jerez. Las lágrimas son más densas y lentas que en el Capítulo 1, lo que anuncia una textura aún más oleosa en boca.\r\n\r\nEn nariz\r\n¡Un golpe de calor aromático! La nariz es un estallido de especias. Los frutos rojos son los protagonistas de esta sinfonía que también lleva canela, nuez moscada, y en el fondo, un toque de madera con un caramelo dulce, a veces algo quemado. Es un aroma complejo y profundo: ¡huele a calidez y sofisticación!\r\n\r\nEn boca\r\nLa entrada en boca es muy suave y dulce. De inmediato aparecen los frutos rojos como uva pasa, una ciruela madura, caramelo y algo de manzana fresca. Como en el caso del Port Cask, es un whisky muy fiel a su nariz. Aparecen algunas notas a miel y algo de nuez, perfectamente equilibrada por el toque seco y especiado del roble.\r\n\r\nRetrogusto/final\r\nEl final es de corto, casi llegando a medio, dejando una sensación cálida en el paladar. Es seco y especiado, con un post-gusto a canela y chocolate amargo. Un cierre que te invita a la meditación.', 2, 0, 1, 1, 2, NULL, 1, '2025-11-17 19:39:31', '2025-11-18 13:41:19', 'No especificada'),
(8, 1, 'Capitulo 3 - Glen Moray Classic', '', 'Capítulo 3: Glen Moray Classic\r\n\r\nEsta tercera botella es oficialmente 100% ex-Bourbon!! Es decir que todo su añejamiento fue en una sola barrica sin ningún finish.\r\nMás adelante verás este contraste increíble entre líquidos que son \'similares\' —el grano destilado para el bourbon por un lado, y la malta para este single malt por el otro— y cómo la misma madera que los contuvo y añejó les da un perfil completamente distinto.\r\n\r\nA estas alturas ya te abras dado cuenta de que la destilería Glen Moray es tu MEJOR puerta de entrada al mundo del whisky, es sin duda la mejor opción en calidad-precio. Esta es la versión clásica de la destilería, madurado completamente en barricas de roble americano que previamente contuvieron bourbon. ¡Por fin estamos frente a frente con el clásico estilo de Speyside! Si bien es cierto que el roble americano recién aparece en Escocia allá por 1945 más o menos, desde ahí, ha estado de manera muy activa en todas las regiones escocesas para convertirse en el nuevo estilo clásico.\r\n\r\nLa Cata: La Base Dorada de Speyside\r\nEste Single Malt es el punto de referencia para quienes buscan un sabor suave, pero con más carácter que un Blend promedio (como el Red Label, el Finest, etc.). Es el whisky perfecto para pasar de \"tomar\" a \"disfrutar\".\r\n\r\nLo que Vemos\r\nEl color es un oro pálido y brillante, sin rastro de los reflejos rojizos que vimos en los Capítulos 1 y 2. Las lágrimas son rápidas y ligeras, fluyendo con presteza por el vaso. Esto anuncia una textura más ligera y accesible en boca.\r\n\r\nEn nariz\r\n¡Pura frescura de Speyside! La nariz es limpia, dulce y alegre. Pera y manzana en almíbar acompañados de un caramelo dulce y pequeños destellos de vainilla en el fondo. Es un aroma directo, limpio y elegante.\r\n\r\nEn boca \r\nLa entrada en boca es ligera, muy suave y fácil de beber, ideal para tomar solo o con un par de gotas de agua. La dulzura es sutil, dominada por la pera que de inmediato se apodera de la situación; la manzana dulce la complementa perfectamente y de fondo tenemos algo de vainilla, a veces un caramelo. A diferencia de los Capítulos 1 y 2, aquí no hay complejidad de frutos secos; es un sabor directo, limpio y refrescante.\r\n\r\nRetrogusto/final\r\nEl final es de corto, casi casi llegando a medio, es muy limpio y diría que no tan seco como los anteriores. Deja un regusto a vainilla cremosa y una leve nota a madera tostada. Un cierre que te deja el paladar listo para el siguiente sorbo.', 3, 0, 1, 1, 3, NULL, 1, '2025-11-17 19:40:17', '2025-11-18 13:41:23', 'No especificada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chapter_categories`
--

CREATE TABLE `chapter_categories` (
  `chapter_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chapter_images`
--

CREATE TABLE `chapter_images` (
  `id` int(11) NOT NULL,
  `chapter_id` int(11) DEFAULT NULL,
  `image_path` varchar(500) NOT NULL,
  `image_order` int(11) DEFAULT 0,
  `caption` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `chapter_images`
--

INSERT INTO `chapter_images` (`id`, `chapter_id`, `image_path`, `image_order`, `caption`, `created_at`) VALUES
(2, 4, '691b7fd4d7492_imagen 9.jpeg', 0, '', '2025-11-17 20:04:36'),
(3, 6, '692743ad12637_88.webp', 1, 'capitulo1', '2025-11-26 18:15:09'),
(4, 7, '69274595ae999_imagen 3.jpeg', 0, '', '2025-11-26 18:23:17'),
(5, 8, '69274632cb47d_imagen 7.jpeg', 0, '', '2025-11-26 18:25:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `chapter_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comments`
--

INSERT INTO `comments` (`id`, `chapter_id`, `user_id`, `content`, `rating`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 4, 2, 'Genial', 4, 1, '2025-11-18 13:17:38', '2025-11-18 13:17:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('tasting','workshop','masterclass','social') DEFAULT 'tasting',
  `location` varchar(500) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `max_participants` int(11) DEFAULT NULL,
  `current_participants` int(11) DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `registration_link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_type`, `location`, `address`, `event_date`, `event_time`, `duration`, `price`, `max_participants`, `current_participants`, `image_path`, `is_active`, `is_featured`, `registration_link`, `created_at`, `updated_at`) VALUES
(1, 'Cata Premium: Whiskies de Islay', 'Una experiencia sensorial explorando los whiskies ahumados de la isla de Islay. Degustaremos 6 whiskies emblemáticos incluyendo Lagavulin, Laphroaig y Ardbeg.', 'tasting', 'Salón Principal El Camino del Whisky', 'Av. San Blas 123, CDE', '2025-12-15', '19:00:00', '2 horas', 50.00, 20, 0, 'event_islay.jpg', 1, 1, 'https://wa.me/595983163300?text=Me%20interesa%20la%20Cata%20Islay', '2025-11-26 18:58:21', '2025-11-26 18:58:21'),
(2, 'Workshop: Iniciación al Whisky', 'Perfecto para principiantes. Aprenderás los fundamentos de la cata, tipos de whisky y cómo apreciar sus matices.', 'workshop', 'Sala VIP - Shopping Paris', 'Shopping Paris, Local 45, CDE', '2025-12-10', '18:30:00', '3 horas', 25.00, 15, 0, 'event_workshop.jpg', 1, 0, 'https://wa.me/595983163300?text=Me%20interesa%20el%20Workshop%20Iniciación', '2025-11-26 18:58:21', '2025-11-26 18:58:21'),
(3, 'Masterclass: Whisky Japonés', 'Descubre la elegancia y precisión de los whiskies del Japón con 5 etiquetas premium incluyendo Yamazaki y Hibiki.', 'masterclass', 'Hotel del Lago', 'Av. del Lago 789, CDE', '2025-12-20', '20:00:00', '2.5 horas', 75.00, 12, 0, 'event_japanese.jpg', 1, 1, 'https://wa.me/595983163300?text=Me%20interesa%20la%20Masterclass%20Japonesa', '2025-11-26 18:58:21', '2025-11-26 18:58:21'),
(4, 'Noche de Bourbon Americano', 'Explora la dulzura y carácter de los whiskies de Kentucky. Incluye bourbons artesanales y limited editions.', 'tasting', 'Bar La Cava', 'Calle Comercio 456, CDE', '2025-12-08', '19:30:00', '2 horas', 40.00, 25, 0, 'event_bourbon.jpg', 1, 0, 'https://wa.me/595983163300?text=Me%20interesa%20Noche%20Bourbon', '2025-11-26 18:58:21', '2025-11-26 18:58:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `home_slider`
--

CREATE TABLE `home_slider` (
  `id` int(11) NOT NULL,
  `chapter_id` int(11) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `image_caption` varchar(500) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT 'Ver Capítulo',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `home_slider`
--

INSERT INTO `home_slider` (`id`, `chapter_id`, `image_path`, `image_caption`, `title`, `subtitle`, `button_text`, `display_order`, `is_active`, `created_at`) VALUES
(8, NULL, 'uploads/slider/slider_1763477658_691c889ae9e7d.jpg', '', '', '', 'Ver Más', 0, 1, '2025-11-18 14:54:18'),
(9, NULL, 'uploads/slider/slider_1763478093_691c8a4d0d16c.jpeg', '', '', '', 'Ver Más', 0, 1, '2025-11-18 15:01:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `newsletter_campaigns`
--

CREATE TABLE `newsletter_campaigns` (
  `id` int(11) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `sent_count` int(11) DEFAULT 0,
  `sent_by` int(11) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `season_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `season_id`, `amount`, `payment_method`, `bank_account`, `transaction_id`, `status`, `created_at`) VALUES
(1, 4, 1, 0.00, 'manual', NULL, NULL, 'completed', '2025-11-18 14:25:58'),
(2, 1, 1, 0.00, 'manual', NULL, NULL, 'completed', '2025-11-26 18:24:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_configs`
--

CREATE TABLE `payment_configs` (
  `id` int(11) NOT NULL,
  `season_id` int(11) DEFAULT NULL,
  `chapter_price` decimal(10,2) DEFAULT 0.00,
  `season_price` decimal(10,2) DEFAULT 0.00,
  `bundle_price` decimal(10,2) DEFAULT 0.00,
  `bank_account` varchar(255) DEFAULT NULL,
  `alias` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `payment_configs`
--

INSERT INTO `payment_configs` (`id`, `season_id`, `chapter_price`, `season_price`, `bundle_price`, `bank_account`, `alias`, `is_active`, `updated_at`) VALUES
(1, NULL, 0.00, 10.00, 5.00, '123456789', 'EL.CAMINO.WHISKY', 1, '2025-11-26 15:04:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_content`
--

CREATE TABLE `project_content` (
  `id` int(11) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_content` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `project_content`
--

INSERT INTO `project_content` (`id`, `section_title`, `section_content`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Apreciado lector:', 'Así como vos, un día —hace ya algunos años— decidí que quería conocer más sobre este apreciado y, para muchos, misterioso espirituoso. Pensé que el camino sería fácil: después de todo, hoy existen infinidad de canales de información y personas dispuestas a compartir su experiencia en casi cualquier tema. ¡Pero grande fue mi sorpresa cuando descubrí que no era así!', 1, 1, '2025-11-26 13:56:44', '2025-11-26 13:56:44'),
(2, 'El Desafío Inicial', 'Pronto me di cuenta de que no existe un camino claro para quienes desean empezar desde cero. La mayoría de los canales hablan de botellas específicas, describen notas de cata y cuentan detalles sobre las destilerías —algo valioso, sin duda—, pero nadie explica qué botellas comprar, dónde hacerlo o cómo leer una etiqueta. Y si encontrás a alguien que más o menos lo hace, suele referirse a un mercado muy distinto al nuestro.', 2, 1, '2025-11-26 13:56:44', '2025-11-26 13:56:44'),
(3, 'La Determinación', 'Aun así, decidí seguir adelante. Con las herramientas que tenía, fui aprendiendo paso a paso, botella a botella, hasta formar mi propio criterio. Cada sorbo era una lección, cada aroma un descubrimiento, y cada botella una nueva página en este libro sensorial que estaba escribiendo con mi paladar.', 3, 1, '2025-11-26 13:56:44', '2025-11-26 13:56:44'),
(4, 'El Nacimiento del Proyecto', 'Con el tiempo, nació en mí la idea de compartir ese aprendizaje y crear una guía sencilla que acompañe a cualquiera que quiera iniciar este recorrido tan apasionante. No quería que otros tuvieran que pasar por las mismas dificultades que yo enfrenté. Quería allanar el camino, hacerlo accesible y disfrutable desde el primer momento.', 4, 1, '2025-11-26 13:56:44', '2025-11-26 13:56:44'),
(5, 'Nuestra Filosofía', 'Creemos que el whisky no es solo una bebida, sino una experiencia cultural, un viaje sensorial que conecta tradiciones, territorios y personas. Cada botella cuenta una historia, cada destilería tiene su alma, y cada cata es una oportunidad para descubrir algo nuevo sobre nosotros mismos.', 5, 1, '2025-11-26 13:56:44', '2025-11-26 13:56:44'),
(6, 'El Mensaje Final', 'Ojalá que esta pequeña guía te ayude a dar tus primeros pasos en este hermoso camino. Que cada sorbo te acerque no solo al entendimiento del whisky, sino al placer de descubrir, aprender y compartir. El camino del whisky es, en definitiva, el camino del conocimiento sensorial.', 6, 1, '2025-11-26 13:56:44', '2025-11-26 13:56:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seasons`
--

CREATE TABLE `seasons` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `is_published` tinyint(1) DEFAULT 0,
  `requires_payment` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `seasons`
--

INSERT INTO `seasons` (`id`, `title`, `subtitle`, `description`, `price`, `is_active`, `is_published`, `requires_payment`, `display_order`, `published_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'TEMPORADA 1', 'Descubre los conceptos básicos de la cata de whisky', 'Una guía completa para empezar tu viaje en el mundo del whisky', 0.00, 1, 1, 1, 1, NULL, NULL, '2025-11-13 00:32:34', '2025-11-18 14:24:59'),
(2, 'TEMPORADA 2', 'Explora los whiskies ahumados de Escocia', 'Profundiza en los whiskies de las islas escocesas', 5.00, 1, 1, 1, 2, NULL, NULL, '2025-11-13 00:32:34', '2025-11-18 13:42:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `site_config`
--

CREATE TABLE `site_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `site_config`
--

INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `description`, `updated_at`) VALUES
(1, 'whatsapp_number', '595983163300', 'Número de WhatsApp para contacto', '2025-11-13 01:02:08'),
(2, 'whatsapp_message', 'Hola! Estoy interesado en El Camino del Whisky', 'Mensaje predeterminado para WhatsApp', '2025-11-13 01:02:08'),
(3, 'bank_name', 'Itaú Paraguay', 'Nombre del banco para transferencias', '2025-11-13 01:02:08'),
(4, 'bank_account', '028192130', 'Número de cuenta bancaria', '2025-11-26 18:09:15'),
(5, 'bank_alias', '2499639-4', 'Alias para transferencias', '2025-11-26 18:09:15'),
(6, 'bank_holder', 'Celso Fernandez', 'Titular de la cuenta bancaria', '2025-11-26 18:09:15'),
(7, 'bank_ruc', '80002188-6', 'RUC de la empresa', '2025-11-26 18:09:15'),
(8, 'site_title', 'EL CAMINO DEL WHISKY', 'Título del sitio web', '2025-11-13 01:02:08'),
(9, 'site_description', 'Experiencias de Cata Premium', 'Descripción del sitio web', '2025-11-13 01:02:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','writer','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`, `is_active`, `first_name`, `last_name`, `phone`, `avatar`, `bio`, `last_login`) VALUES
(1, 'admin', 'admin@elcaminodelwhisky.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-11-13 00:32:34', '2025-11-13 00:32:34', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Gustavo Velazquez', 'gustavi11164@gmail.com', '$2y$10$NWlPnIk4Wkx9Bw8xOqzgnewBEAsa8xanitzXMCPOj0X4Lm99qzmGK', 'user', '2025-11-13 15:24:25', '2025-11-13 15:24:25', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Adrian Velazquez', 'adrian@adrian.com', '$2y$10$AjOanBf6AzyfJRoNSuRrHudIGHdwQena6noaPrdJm5SNhOkGn30EK', 'user', '2025-11-18 14:24:04', '2025-11-18 14:24:04', 1, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `season_id` int(11) NOT NULL,
  `is_completed` tinyint(4) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `last_position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `chapter_id`, `season_id`, `is_completed`, `completed_at`, `progress_percentage`, `last_position`, `created_at`, `updated_at`) VALUES
(1, 2, 4, 1, 1, '2025-11-18 13:59:22', 100, 0, '2025-11-18 13:59:22', '2025-11-18 13:59:22'),
(2, 2, 6, 1, 1, '2025-11-18 13:59:38', 100, 0, '2025-11-18 13:59:38', '2025-11-18 13:59:38'),
(3, 2, 7, 1, 1, '2025-11-18 14:09:45', 100, 0, '2025-11-18 14:09:45', '2025-11-18 14:09:45'),
(4, 2, 8, 1, 1, '2025-11-18 14:09:48', 100, 0, '2025-11-18 14:09:48', '2025-11-18 14:09:48'),
(5, 4, 7, 1, 1, '2025-11-18 14:24:30', 100, 0, '2025-11-18 14:24:30', '2025-11-18 14:24:30'),
(6, 4, 4, 1, 1, '2025-11-18 14:25:08', 100, 0, '2025-11-18 14:25:08', '2025-11-18 14:25:08'),
(7, 1, 4, 1, 1, '2025-11-26 14:22:06', 100, 0, '2025-11-26 14:22:06', '2025-11-26 14:22:06'),
(8, 1, 6, 1, 1, '2025-11-26 18:03:07', 100, 0, '2025-11-26 18:03:07', '2025-11-26 18:03:07'),
(9, 1, 7, 1, 1, '2025-11-26 18:24:13', 100, 0, '2025-11-26 18:24:13', '2025-11-26 18:24:13'),
(10, 1, 8, 1, 1, '2025-11-26 18:26:01', 100, 0, '2025-11-26 18:26:01', '2025-11-26 18:26:01');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alliances`
--
ALTER TABLE `alliances`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `season_id` (`season_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `chapter_categories`
--
ALTER TABLE `chapter_categories`
  ADD PRIMARY KEY (`chapter_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `chapter_images`
--
ALTER TABLE `chapter_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chapter_id` (`chapter_id`);

--
-- Indices de la tabla `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chapter_id` (`chapter_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `home_slider`
--
ALTER TABLE `home_slider`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chapter_id` (`chapter_id`);

--
-- Indices de la tabla `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indices de la tabla `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `season_id` (`season_id`);

--
-- Indices de la tabla `payment_configs`
--
ALTER TABLE `payment_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `season_id` (`season_id`);

--
-- Indices de la tabla `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indices de la tabla `project_content`
--
ALTER TABLE `project_content`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `seasons`
--
ALTER TABLE `seasons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `site_config`
--
ALTER TABLE `site_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_chapter` (`user_id`,`chapter_id`),
  ADD KEY `chapter_id` (`chapter_id`),
  ADD KEY `season_id` (`season_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alliances`
--
ALTER TABLE `alliances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `chapters`
--
ALTER TABLE `chapters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `chapter_images`
--
ALTER TABLE `chapter_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `home_slider`
--
ALTER TABLE `home_slider`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `payment_configs`
--
ALTER TABLE `payment_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `project_content`
--
ALTER TABLE `project_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seasons`
--
ALTER TABLE `seasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `site_config`
--
ALTER TABLE `site_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `chapters`
--
ALTER TABLE `chapters`
  ADD CONSTRAINT `chapters_ibfk_1` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  ADD CONSTRAINT `chapters_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `chapter_categories`
--
ALTER TABLE `chapter_categories`
  ADD CONSTRAINT `chapter_categories_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chapter_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `chapter_images`
--
ALTER TABLE `chapter_images`
  ADD CONSTRAINT `chapter_images_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `home_slider`
--
ALTER TABLE `home_slider`
  ADD CONSTRAINT `home_slider_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD CONSTRAINT `newsletter_campaigns_ibfk_1` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`);

--
-- Filtros para la tabla `payment_configs`
--
ALTER TABLE `payment_configs`
  ADD CONSTRAINT `payment_configs_ibfk_1` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`);

--
-- Filtros para la tabla `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `private_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seasons`
--
ALTER TABLE `seasons`
  ADD CONSTRAINT `seasons_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_3` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
