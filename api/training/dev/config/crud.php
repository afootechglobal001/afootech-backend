<?php
function selectQuery(mysqli $conn, string $query, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($query);
    if ($types && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}
function insertQuery(mysqli $conn, string $query, string $types = '', array $params = []): int
{
	$stmt = $conn->prepare($query);
	if ($types && !empty($params)) {
		$stmt->bind_param($types, ...$params);
	}
	$stmt->execute();
	$insertId = $stmt->insert_id;
	$stmt->close();

	return $insertId; /// ID of the inserted row
}
function updateQuery(mysqli $conn, string $query, string $types = '', array $params = []): int
{
	$stmt = $conn->prepare($query);
	if ($types && !empty($params)) {
		$stmt->bind_param($types, ...$params);
	}
	$stmt->execute();
	$affectedRows = $stmt->affected_rows;
	$stmt->close();

	return $affectedRows; /// number of affected rows for UPDATE
}
function deleteQuery(mysqli $conn, string $query, string $types = '', array $params = []): int
{
	$stmt = $conn->prepare($query);
	if ($types && !empty($params)) {
		$stmt->bind_param($types, ...$params);
	}
	$stmt->execute();
	$affectedRows = $stmt->affected_rows;
	$stmt->close();

	return $affectedRows; /// number of affected rows for DELETE
}