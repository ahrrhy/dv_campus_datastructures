<?php

declare(strict_types=1);

class Fixtures
{
    public const CURRENT_POSITION_ID = 10;
    public const CURRENT_DAILY_INCOME_ID = 69;
    public const CURRENT_EMPLOYEE_ID = 27;
    public const CURRENT_EMPLOYEES_SALARY_PAYMENT_ID = 162;
    public const CURRENT_SALARY_ID = 162;
    public const CURRENT_TRANSPORT_ID = 16;
    public const CURRENT_TRANSPORT_ROUTES_ID = 6;
    public const CURRENT_TRANSPORT_TYPE_ID = 4;

    /**
     * @var PDO $connection
     */
    private static $connection;

    /**
     * @return void
     */
    public function generate(): void
    {
        $connection = $this->getConnection();

        try {
            $connection->beginTransaction();
            $this->cleanup();
            $connection->commit();

            $connection->beginTransaction();
            $this->generatePositions(7);
            $this->generateEmployees(55);
            $this->generateTransports(55);
            $this->generateSalaries(100000);
            $this->generateEmployeesSalaryPayments();
            $this->generateDailyIncomes(1000000);
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            echo $e->getMessage();
        }
    }

    private function getRandomName(): string
    {
        static $randomNames = ['Norbert','Damon','Laverna','Annice','Brandie','Emogene','Cinthia','Magaret','Daria','Ellyn','Rhoda','Debbra','Reid','Desire','Sueann','Shemeka','Julian','Winona','Billie','Michaela','Loren','Zoraida','Jacalyn','Lovella','Bernice','Kassie','Natalya','Whitley','Katelin','Danica','Willow','Noah','Tamera','Veronique','Cathrine','Jolynn','Meridith','Moira','Vince','Fransisca','Irvin','Catina','Jackelyn','Laurine','Freida','Torri','Terese','Dorothea','Landon','Emelia'];
        return $randomNames[array_rand($randomNames)] . ' ' . $randomNames[array_rand($randomNames)];
    }

    /**
     * @return string
     */
    private function getRandomPosition(): string
    {
        $randomPosition = ['system_administrator', 'cleaner', 'manager'];
        $randomLevel = ['junior', 'senior', 'chief'];

        return $randomLevel[array_rand($randomLevel)] . '_' . $randomPosition[array_rand($randomPosition)];
    }

    private function getRandomProduct(): string
    {
        return uniqid();
    }

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if (null === self::$connection) {
            self::$connection = new PDO('mysql:host=127.0.0.1:3357;dbname=CherkasyElektroTrans', 'root', 'root', []);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$connection;
    }

    private function cleanup(): void
    {
        $connection = $this->getConnection();
        $connection->exec('DELETE FROM employee WHERE employee_id > ' . $this::CURRENT_EMPLOYEE_ID);
        $connection->exec('ALTER TABLE employee AUTO_INCREMENT = ' . $this::CURRENT_EMPLOYEE_ID);
        $connection->exec('DELETE FROM transport WHERE transport_id > ' . $this::CURRENT_TRANSPORT_ID);
        $connection->exec('ALTER TABLE transport AUTO_INCREMENT = ' . $this::CURRENT_TRANSPORT_ID);
        $connection->exec('DELETE FROM position WHERE position_id > ' . $this::CURRENT_POSITION_ID);
        $connection->exec('ALTER TABLE position AUTO_INCREMENT = ' . $this::CURRENT_POSITION_ID);
        $connection->exec('DELETE FROM salary WHERE salary_id > ' . $this::CURRENT_SALARY_ID);
        $connection->exec('ALTER TABLE salary AUTO_INCREMENT = ' . $this::CURRENT_SALARY_ID);
        $connection->exec('DELETE FROM daily_income WHERE income_id > ' . $this::CURRENT_DAILY_INCOME_ID);
        $connection->exec('ALTER TABLE daily_income AUTO_INCREMENT = ' . $this::CURRENT_DAILY_INCOME_ID);
        $connection->exec('DELETE FROM employees_salary_payment WHERE payment_id > ' . $this::CURRENT_EMPLOYEES_SALARY_PAYMENT_ID);
        $connection->exec('ALTER TABLE employees_salary_payment AUTO_INCREMENT = ' . $this::CURRENT_EMPLOYEES_SALARY_PAYMENT_ID);
    }

    /**
     * Generate positions
     * @param int $quantity
     */
    private function generatePositions(int $quantity): void
    {
        $connection = $this->getConnection();
        $positionName = '';
        $canDrive = 0;
        $statement = $connection->prepare(<<<SQL
    INSERT INTO position (position_name, can_drive)
    VALUES (:position_name, :can_drive);
    SQL
        );
        $statement->bindParam(':position_name', $positionName);
        $statement->bindParam(':can_drive', $canDrive);
        for ($positionId = $this::CURRENT_POSITION_ID; $positionId < $this::CURRENT_POSITION_ID + $quantity; $positionId++) {
            $positionName = $this->getRandomPosition();
            $statement->execute();
        }
    }

    /**
     * @param int $quantity
     * @throws Exception
     */
    public function generateEmployees(int $quantity): void
    {
        $connection = $this->getConnection();
        $currentTimestamp = time();

        // === CREATE Employees ===
        $start = microtime(true);

        $firstName = $lastName = $userName = $positionId = $startDate = $dob = '';
        $minAgeStartDate = $currentTimestamp - (31556952 * 20);
        $minAgeTimestamp = $currentTimestamp - (31556952 * 45);
        $maxAgeTimestamp = $currentTimestamp - (31556952 * 16);
        $statement = $connection->prepare(<<<SQL
    INSERT INTO employee (first_name, last_name, position_id, start_date, dob)
    VALUES (:first_name, :last_name, :position_id, :start_date, :dob)
    ON DUPLICATE KEY UPDATE dob=VALUES(dob), position_id=VALUES(position_id);
SQL
        );
        $statement->bindParam(':position_id', $positionId);
        $statement->bindParam(':first_name', $firstName);
        $statement->bindParam(':last_name', $lastName);
        $statement->bindParam(':start_date', $startDate);
        $statement->bindParam(':dob', $dob);

        for ($employeeId = $this::CURRENT_EMPLOYEE_ID; $employeeId < $quantity + $this::CURRENT_EMPLOYEE_ID; $employeeId++) {
            $positionId = random_int(1, $this::CURRENT_POSITION_ID);
            $userName = explode(' ', $this->getRandomName());
            $firstName = $userName[0];
            $lastName = $userName[0];
            $timestampDob = random_int($minAgeTimestamp, $maxAgeTimestamp);
            $timestampStartDate = random_int($minAgeStartDate, $maxAgeTimestamp);
            $startDate = date('Y-m-d', $timestampStartDate);
            $dob = date('Y-m-d', $timestampDob);
            $statement->execute();
        }

        echo 'Create employees: ' . (microtime(true) - $start) . "\n";
    }

    public function generateTransports(int $quantity)
    {
        $connection = $this->getConnection();
        $transportUID = $transportTypeId = '';
        $statement = $connection->prepare(<<<SQL
    INSERT INTO transport (transport_type_id, transport_uid)
    VALUES (:transport_type_id, :transport_uid);
SQL
        );
        $statement->bindParam(':transport_type_id', $transportTypeId);
        $statement->bindParam(':transport_uid', $transportUID);

        for ($transportId = $this::CURRENT_TRANSPORT_ID; $transportId < $quantity + $this::CURRENT_TRANSPORT_ID; $transportId++) {
            $transportTypeId = random_int(1, $this::CURRENT_TRANSPORT_TYPE_ID);
            $transportUID = $this->getRandomProduct();
            $statement->execute();
        }
    }

    public function generateSalaries(int $quantity): void
    {
        $connection = $this->getConnection();
        $currentTimestamp = time();

        // === CREATE Salaries ===
        $start = microtime(true);
        $salaryValue = $salaryDate = '';
        $statement = $connection->prepare(<<<SQL
    INSERT INTO salary (salary_value, salary_date)
    VALUES (:salary_value, :salary_date)
SQL
        );
        $statement->bindParam(':salary_value', $salaryValue, PDO::PARAM_INT);
        $statement->bindParam(':salary_date', $salaryDate);
        for ($salaryId = $this::CURRENT_SALARY_ID; $salaryId < $quantity + $this::CURRENT_SALARY_ID; $salaryId++) {
                $salaryValue = random_int(8000, 100000);
                $timestamp = random_int($currentTimestamp - 31556952, $currentTimestamp);
                $salaryDate = date('Y-m-d', $timestamp);
                $statement->execute();
        }

        echo 'Create salaries: ' . (microtime(true) - $start) . "\n";
    }

    public function generateEmployeesSalaryPayments(): void
    {
        $connection = $this->getConnection();

        $salaryId = $employeeId = '';
        $statement = $connection->prepare(<<<SQL
    INSERT INTO employee_salary_payment (salary_id, employee_id)
    VALUES (:salary_id, :employee_id)
SQL
        );
        $statement->bindParam(':salary_id', $salaryId, PDO::PARAM_INT);
        $statement->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $employeeIdsStatement = $connection->query('SELECT employee_id FROM employee');
        $employeeIds = $employeeIdsStatement->fetchAll(PDO::FETCH_COLUMN);
        $salaryIdsStatement = $connection->query('SELECT employee_id FROM employee');
        $salaryIds = $salaryIdsStatement->fetchAll(PDO::FETCH_COLUMN);
        $salariesPerEmployee = (count($salaryIds) - $this::CURRENT_EMPLOYEES_SALARY_PAYMENT_ID) / count($employeeIds);
        foreach ($employeeIds as $index => $employeeIdValue) {
            for ($i = 0; $i < $salariesPerEmployee; $i++) {
                $employeeId = $employeeIdValue;
                $randomSalary = random_int($this::CURRENT_SALARY_ID + 1, count($salaryIds));
                $salaryId = $randomSalary;
                $statement->execute();

                unset($salaryIds[$randomSalary]);
            }
        }
    }

    public function generateDailyIncomes(int $quantity): void
    {
        $currentTimestamp = time();
        $connection = $this->getConnection();
        $employeeId = $transportId = $incomeValue = $incomeDate = $routeId = '';
        $start = microtime(true);
        $statement = $connection->prepare(<<<SQL
    INSERT INTO daily_income (employee_id, transport_id, income_value, income_date, route_id)
    VALUES (:employee_id, :transport_id, :income_value, :income_date, :route_id)
SQL
        );
        $statement->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $statement->bindParam(':transport_id', $transportId, PDO::PARAM_INT);
        $statement->bindParam(':income_value', $incomeValue, PDO::PARAM_INT);
        $statement->bindParam(':income_date', $incomeDate);
        $statement->bindParam(':route_id', $routeId, PDO::PARAM_INT);
        $employeeIdsStatement = $connection->query(
            'SELECT e.employee_id FROM employee AS e WHERE e.position_id = 8'
        );
        $employeeIds = $employeeIdsStatement->fetchAll(PDO::FETCH_COLUMN);
        $transportStatement = $connection->query(
            'SELECT t.transport_id FROM transport t WHERE t.transport_type_id = 1'
        );
        $transportIds = $transportStatement->fetchAll(PDO::FETCH_COLUMN);
        $transportRoutesStatement = $connection->query(
            'SELECT route_id FROM transport_routes'
        );
        $rotesIds = $transportRoutesStatement->fetchAll(PDO::FETCH_COLUMN);

        for ($dailyIncomeId = $this::CURRENT_DAILY_INCOME_ID; $dailyIncomeId < $quantity + $this::CURRENT_DAILY_INCOME_ID; $dailyIncomeId++) {
            $employeeId = $employeeIds[array_rand($employeeIds)];
            $transportId = $transportIds[array_rand($transportIds)];
            $incomeValue = random_int(500, 12000);
            $timestamp = random_int($currentTimestamp - 31556952, $currentTimestamp);
            $incomeDate = date('Y-m-d', $timestamp);
            $routeId = $rotesIds[array_rand($rotesIds)];
            $statement->execute();
        }

        echo 'Create salaries: ' . (microtime(true) - $start) . "\n";
    }
}
$fixturesGenerator = new Fixtures();
$fixturesGenerator->generate();
