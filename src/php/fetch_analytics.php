<?php
/**
 * Filename: fetch_analytics.php
 * Author: Md Rifat
 * Description: This file contains the implementation of fetching and processing analytics data from the server.
 */


require 'functions.php'; // Database connection and helper functions

header('Content-Type: application/json');

// Get filters
//$organizationID = $_GET['organizationID'] ?? null;
$organisationID = 1;
// Get request type
$type = $_GET['type'] ?? '';


$pdo= getConnection();


// Get request type
$type = $_GET['type'] ?? '';

if ($type === 'overview') {

    $query = "
    SELECT 
    -- Total users in the organization
    (SELECT COUNT(*) 
     FROM userTable 
     WHERE organisationID = :orgID) AS totalUsers,

    -- Active users (logged in within the past 7 days)
    (SELECT COUNT(DISTINCT userID) 
     FROM employeeActivityLog 
     WHERE activityType = 'Login' 
       AND activityDate >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
       AND userID IN (SELECT userID FROM userTable WHERE organisationID = :orgID)) AS activeUsers,

    -- Completed stories
    (SELECT COUNT(*) 
     FROM storyCompletionLog 
     WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = :orgID)) AS completedStories,

    -- Completed episodes
    (SELECT COUNT(*) 
     FROM episodeCompletionLog 
     WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = :orgID)) AS completedEpisodes,

    -- Average story completion time
    (SELECT AVG(durationInSeconds) 
     FROM storyCompletionLog 
     WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = :orgID)) AS avgStoryTime,

    -- Average episode completion time
    (SELECT AVG(durationInSeconds) 
     FROM episodeCompletionLog 
     WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = :orgID)) AS avgEpisodeTime

";



    $stmt = $pdo->prepare($query);
    $stmt->execute([':orgID' => $organisationID]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}




if ($type === 'organization-comparison') {
    $query = "
    SELECT 
        o.name AS organization,
        (SELECT COUNT(*) FROM userTable WHERE organisationID = o.organisationID) AS totalUsers,
        (SELECT COUNT(DISTINCT userID) FROM employeeActivityLog 
            WHERE activityType = 'Login' 
            AND activityDate >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
            AND userID IN (SELECT userID FROM userTable WHERE organisationID = o.organisationID)) AS activeUsers,
        (SELECT COUNT(*) FROM storyCompletionLog 
            WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = o.organisationID)) AS completedStories,
        (SELECT COUNT(*) FROM episodeCompletionLog 
            WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = o.organisationID)) AS completedEpisodes,
        (SELECT AVG(durationInSeconds) FROM storyCompletionLog 
            WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = o.organisationID)) AS avgStoryTime,
        (SELECT AVG(durationInSeconds) FROM episodeCompletionLog 
            WHERE userID IN (SELECT userID FROM userTable WHERE organisationID = o.organisationID)) AS avgEpisodeTime
    FROM organisationTable o
";


    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
    exit;
}








if ($type === 'user-progress') {
    $timeRange = $_GET['timeRange'] ?? 'month'; // Default to 'month' if not provided

    $dateCondition = ($timeRange === 'week') 
        ? "DATE_SUB(NOW(), INTERVAL 1 WEEK)" 
        : "DATE_SUB(NOW(), INTERVAL 1 MONTH)";

    $query = "
        SELECT 
            u.forename AS userName,
            DATE(ec.startTime) AS date,
            COUNT(DISTINCT ec.episodeID) AS episodesCompleted,
            COUNT(DISTINCT sc.storyID) AS storiesCompleted
        FROM userTable u
        LEFT JOIN episodeCompletionLog ec ON u.userID = ec.userID AND ec.startTime >= $dateCondition
        LEFT JOIN storyCompletionLog sc ON u.userID = sc.userID AND sc.startTime >= $dateCondition
        WHERE ec.startTime IS NOT NULL OR sc.startTime IS NOT NULL
        GROUP BY u.forename, DATE(ec.startTime)
        ORDER BY DATE(ec.startTime) ASC
    ";

    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
    exit;
}