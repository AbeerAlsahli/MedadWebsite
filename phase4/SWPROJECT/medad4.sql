-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 30, 2025 at 03:45 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medad4`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bookingID` bigint(20) UNSIGNED NOT NULL,
  `userID` bigint(20) UNSIGNED NOT NULL,
  `eventID` bigint(20) UNSIGNED NOT NULL,
  `bookingDate` date NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'booked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bookingID`, `userID`, `eventID`, `bookingDate`, `status`) VALUES
(8, 3, 5, '2025-11-30', 'booked'),
(9, 1, 2, '2025-11-10', 'booked'),
(10, 3, 2, '2025-11-10', 'booked');

-- --------------------------------------------------------

--
-- Table structure for table `bookmark`
--

CREATE TABLE `bookmark` (
  `bookmarkID` bigint(20) UNSIGNED NOT NULL,
  `userID` bigint(20) UNSIGNED NOT NULL,
  `itemType` enum('event','club') COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemID` bigint(20) UNSIGNED NOT NULL,
  `dateAdded` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookmark`
--

INSERT INTO `bookmark` (`bookmarkID`, `userID`, `itemType`, `itemID`, `dateAdded`) VALUES
(24, 3, 'club', 2, '2025-11-30');

-- --------------------------------------------------------

--
-- Table structure for table `club`
--

CREATE TABLE `club` (
  `clubID` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'image/event3.jpg',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `club`
--

INSERT INTO `club` (`clubID`, `name`, `description`, `location`, `image_path`, `created_by`, `created_at`) VALUES
(1, 'نادي أدباء المملكة', 'ملتقى للأدباء من جميع نواحي المملكة لتوفير تجربة تفاعلية مع أقرانهم ومناقشة الأعمال الأدبية المميزة.', 'الرياض، مركز الملك فهد الثقافي', 'image/event6.jpg', NULL, '2025-11-29 22:52:11'),
(2, 'نادي نبض الحرف', 'مجمع أدبي يوفر فرصة التعرف على أصول الخط العربي ودمجه في الوقت الحاضر مع الحفاظ على التراث الخطي.', 'الرياض، مركز الملك فهد الثقافي', 'image/event7.jpg', NULL, '2025-11-29 22:52:11'),
(3, 'نادي سطور', 'ملتقى لمحبي الأدب السعودي وجميع تطبيقاته، يهدف إلى نشر الثقافة الأدبية ودعم الكتاب الشباب.', 'جدة، مكتبة الأدب السعودي', 'image/event8.jpg', NULL, '2025-11-29 22:52:11');

-- --------------------------------------------------------

--
-- Table structure for table `club_memberships`
--

CREATE TABLE `club_memberships` (
  `userID` bigint(20) UNSIGNED NOT NULL,
  `clubID` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `commentID` bigint(20) UNSIGNED NOT NULL,
  `userID` bigint(20) UNSIGNED NOT NULL,
  `eventID` bigint(20) UNSIGNED NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`commentID`, `userID`, `eventID`, `content`, `timestamp`) VALUES
(5, 3, 2, 'رائعه وممتعه', '2025-11-30 18:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `eventID` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `location` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `capacity` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'image/event4.jpg',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`eventID`, `title`, `date`, `location`, `description`, `capacity`, `status`, `image_path`, `created_by`, `created_at`) VALUES
(1, 'أمسية شعرية سعودية', '2025-12-05', 'الدمام، فندق هيلتون', 'فعالية تجمع العديد من الشعراء من مختلف مناطق المملكة لمناقشة ومحاورة ثقافة الشعر السعودي وتحدي شعري لمحبّي الشعر النبطي.', 150, 'open', 'image/event1.jpg', NULL, '2025-11-29 22:52:11'),
(2, 'معرض الأدب السعودي', '2025-11-15', 'الرياض، قاعة المعارض الكبرى', 'معرض يسلط الضوء على المختصين بالأدب السعودي وأبرز أعمالهم في مجالاته. فرصة لاكتشاف الأعمال الأدبية النادرة والالتقاء بالمؤلفين الشباب.', 200, 'open', 'image/event2.jpg', NULL, '2025-11-29 22:52:11'),
(3, 'ندوة الأدب السعودي الحديث', '2025-11-20', 'الرياض، مركز الملك عبدالعزيز الثقافي', 'نقاش مفتوح للمهتمين بالأدب السعودي لتوفير فرصة الاستفادة من الخبرات. جلسة حوارية تجمع الأدباء الشباب لتبادل الآراء حول الأدب الحديث.', 100, 'open', 'image/event3.jpg', NULL, '2025-11-29 22:52:11'),
(4, 'ورشة كتابة القصة القصيرة', '2025-11-25', 'جدة، مكتبة الأدب السعودي', 'تجربة تفاعلية لتجربة قص الحكايات بأسلوب القصة القصيرة المشوق. ورش عملية لتطوير مهارات الكتابة والإبداع الأدبي.', 50, 'open', 'image/event4.jpg', NULL, '2025-11-29 22:52:11'),
(5, 'محاضرة تطور الخط العربي', '2025-12-10', 'الرياض، مركز الملك فهد الثقافي', 'محاضرة شيقة لفهم تطور الخط العربي وتأثيره على الأدب السعودي عبر العصور.', 80, 'open', 'image/event5.jpg', NULL, '2025-11-29 22:52:11');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notificationID` bigint(20) UNSIGNED NOT NULL,
  `userID` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sentTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthdate` date DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interests` text COLLATE utf8mb4_unicode_ci,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `notificationPreferences` text COLLATE utf8mb4_unicode_ci,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'image/user.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `name`, `email`, `birthdate`, `password`, `role`, `interests`, `bio`, `notificationPreferences`, `profile_image`) VALUES
(1, 'salma saleh', 'salmas@medad.sa', '2025-11-15', '$2y$10$IIYhc.mfu2m23aSq6jxYIOcjVRNrJdp2dw0cXwSyUV0q/V0posBwK', 'user', 'المقال الأدبي,سيرة ذاتية', 'ss', 'إشعارات داخل المنصة,رسائل قصيرة (SMS)', 'image/user.jpg'),
(2, 'salma saleh', 'salmasaleh@medad.sa', '2009-11-11', '$2y$10$KdIt3z1txG3BB8fsMf7OG.DPKKMKtVSMI6mX2Atrfes/eJCLpdYEm', 'admin', 'المقال الأدبي,التاريخ الأدبي,النقد الأدبي', 'Main admin of Medad', 'إشعارات داخل المنصة,رسائل قصيرة (SMS),البريد الإلكتروني', 'uploads/admin_13.jpg'),
(3, 'abeer', 'abeer@medad.sa', '2009-11-07', '$2y$10$xiPTQjMXb04Kwvl.rtHyruP2YNteNiek5VJtBTudi7EBFNzXM579G', 'user', 'المقال الأدبي,سيرة ذاتية', 'User', 'إشعارات داخل المنصة,البريد الإلكتروني', 'uploads/user_3.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bookingID`),
  ADD KEY `fk_booking_user` (`userID`),
  ADD KEY `fk_booking_event` (`eventID`);

--
-- Indexes for table `bookmark`
--
ALTER TABLE `bookmark`
  ADD PRIMARY KEY (`bookmarkID`),
  ADD KEY `fk_bookmark_user` (`userID`);

--
-- Indexes for table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`clubID`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `club_memberships`
--
ALTER TABLE `club_memberships`
  ADD PRIMARY KEY (`userID`,`clubID`),
  ADD KEY `fk_cm_club` (`clubID`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`commentID`),
  ADD KEY `fk_comment_user` (`userID`),
  ADD KEY `fk_comment_event` (`eventID`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`eventID`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notificationID`),
  ADD KEY `fk_notification_user` (`userID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bookingID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bookmark`
--
ALTER TABLE `bookmark`
  MODIFY `bookmarkID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `club`
--
ALTER TABLE `club`
  MODIFY `clubID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `commentID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `eventID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notificationID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_event` FOREIGN KEY (`eventID`) REFERENCES `event` (`eventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `bookmark`
--
ALTER TABLE `bookmark`
  ADD CONSTRAINT `fk_bookmark_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `club`
--
ALTER TABLE `club`
  ADD CONSTRAINT `club_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`userID`);

--
-- Constraints for table `club_memberships`
--
ALTER TABLE `club_memberships`
  ADD CONSTRAINT `fk_cm_club` FOREIGN KEY (`clubID`) REFERENCES `club` (`clubID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_event` FOREIGN KEY (`eventID`) REFERENCES `event` (`eventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`userID`);

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
