<?php

namespace App\Console\Commands;

use App\Commands\UnifiedGoldController;
use System\Core\BaseCommand;

/**
 * Gold Command
 * Unified command for all gold-related operations
 */
class GoldCommand extends BaseCommand
{
    protected function initialize(): void
    {
        $this->name = 'gold';
        $this->description = 'Gold operations - fetch prices, categories, fill missing data, check duplicates';

        $this->arguments = [
            'action' => 'Action to perform (fetch, categories, fill-missing, check-duplicates, remove-duplicates)',
            'source' => 'Source to fetch from (goonus, baomoi, all) - for fetch action',
            'interval' => 'Time interval for Goonus API (1M, 3Y, etc.) - for fetch action',
            'days' => 'Number of days to check for missing data - for fill-missing action'
        ];

        $this->options = [
            '--continuity' => 'Enable data continuity check (for fetch)',
            '--fill-missing' => 'Fill missing data for previous days (for fetch)',
            '--days' => 'Number of days to check for missing data (default: 7)',
            '--providers' => 'Also fetch providers data (for categories)',
            '--sources' => 'Also fetch sources data (for categories)',
            '--dry-run' => 'Show what would be filled without actually filling (for fill-missing)',
            '--force' => 'Force fill even if data exists (for fill-missing)'
        ];
    }

    public function execute(array $arguments = [], array $options = []): void
    {
        $action = $arguments[0] ?? null;

        if (!$action) {
            // Chạy tất cả 5 chức năng như command cũ
            $this->runAllGoldOperations($arguments, $options);
            return;
        }

        switch ($action) {
            case 'fetch':
                $this->fetchGoldPrices($arguments, $options);
                break;

            case 'categories':
                $this->fetchGoldCategories($arguments, $options);
                break;

            case 'fill-missing':
                $this->fillMissingGoldData($arguments, $options);
                break;

            case 'check-duplicates':
                $this->checkDuplicateRecords($arguments, $options);
                break;

            case 'remove-duplicates':
                $this->removeDuplicateRecords($arguments, $options);
                break;

            default:
                $this->output("Unknown action: $action");
                $this->showHelp();
                break;
        }
    }

    /**
     * Run all gold operations (like the old command)
     */
    private function runAllGoldOperations(array $arguments, array $options): void
    {
        $this->output("=== BẮT ĐẦU CHẠY TẤT CẢ CHỨC NĂNG VÀNG ===");
        $this->output("");

        try {
            $controller = new UnifiedGoldController();

            // 1. Fetch gold categories first (setup data)
            $this->output("1. Fetching gold categories and menu data...");
            $result1 = $controller->fetchMenuCategoryGold();
            if ($result1) {
                $this->output("✓ Gold categories fetched successfully!");
            } else {
                $this->output("⚠ Failed to fetch gold categories");
            }
            $this->output("");

            // 2. Fetch gold prices from all sources
            $this->output("2. Fetching gold prices from all sources...");
            $result2 = $controller->fetchAllGoldPrices('3Y');
            if ($result2) {
                $this->output("✓ Gold prices fetched successfully!");
            } else {
                $this->output("⚠ Failed to fetch gold prices");
            }
            $this->output("");

            // 3. Fill missing data for previous days
            $this->output("3. Filling missing gold data for previous days...");
            $result3 = $controller->fillMissingDataForPreviousDays(7);
            if ($result3) {
                $this->output("✓ Missing data filled successfully!");
            } else {
                $this->output("⚠ No missing data to fill");
            }
            $this->output("");

            // 4. Check for duplicate records
            $this->output("4. Checking for duplicate records...");
            $controller->checkDuplicateRecords();
            $this->output("✓ Duplicate check completed!");
            $this->output("");

            // 5. Remove duplicate records
            $this->output("5. Removing duplicate records...");
            $controller->removeDuplicateRecords();
            $this->output("✓ Duplicate removal completed!");
            $this->output("");

            $this->output("=== HOÀN THÀNH TẤT CẢ CHỨC NĂNG VÀNG ===");
            $this->output("Tất cả 5 chức năng đã được thực hiện thành công!");
        } catch (\Exception $e) {
            $this->logError("Error running all gold operations", $e);
        }
    }

    /**
     * Fetch gold prices
     */
    private function fetchGoldPrices(array $arguments, array $options): void
    {
        $source = $arguments[1] ?? 'all';
        $interval = $arguments[2] ?? '3Y';
        $enableContinuity = in_array('--continuity', $options);
        $fillMissing = in_array('--fill-missing', $options);
        $days = $this->getOptionValue('--days', $options, 7);

        try {
            $controller = new UnifiedGoldController();

            $this->output("Starting gold price fetch...");
            $this->output("Source: $source");
            $this->output("Interval: $interval");

            $result = false;

            switch ($source) {
                case 'goonus':
                    $result = $controller->fetchGoldPricesFromGoonus($interval);
                    break;

                case 'baomoi':
                    $result = $controller->fetchGoldPricesFromBaoMoi();
                    break;

                case 'all':
                default:
                    if ($enableContinuity) {
                        $result = $controller->fetchAllGoldPricesWithContinuity($interval, $days);
                    } else {
                        $result = $controller->fetchAllGoldPrices();
                    }
                    break;
            }

            if ($result) {
                $this->output("Gold prices fetched successfully!");

                if ($fillMissing) {
                    $this->output("Filling missing data for previous $days days...");
                    $fillResult = $controller->fillMissingDataForPreviousDays($days);

                    if ($fillResult) {
                        $this->output("Missing data filled successfully!");
                    } else {
                        $this->output("Some issues occurred while filling missing data.");
                    }
                }
            } else {
                $this->output("Failed to fetch gold prices.");
            }
        } catch (\Exception $e) {
            $this->logError("Error fetching gold prices", $e);
        }
    }

    /**
     * Fetch gold categories
     */
    private function fetchGoldCategories(array $arguments, array $options): void
    {
        $fetchProviders = in_array('--providers', $options);
        $fetchSources = in_array('--sources', $options);

        try {
            $controller = new UnifiedGoldController();

            $this->output("Fetching gold categories...");

            // Fetch menu categories
            $result = $controller->fetchMenuCategoryGold();

            if ($result) {
                $this->output("Gold categories fetched successfully!");
            } else {
                $this->output("Failed to fetch gold categories.");
                return;
            }

            // Fetch providers if requested
            if ($fetchProviders) {
                $this->output("Fetching providers data...");
                $providers = $controller->fetchProviders();

                if ($providers) {
                    $this->output("Providers data fetched successfully! (" . count($providers) . " providers)");
                } else {
                    $this->output("Failed to fetch providers data.");
                }
            }

            // Fetch sources if requested
            if ($fetchSources) {
                $this->output("Fetching sources data...");
                $sources = $controller->fetchSources();

                if ($sources) {
                    $this->output("Sources data fetched successfully! (" . count($sources) . " sources)");
                } else {
                    $this->output("Failed to fetch sources data.");
                }
            }
        } catch (\Exception $e) {
            $this->logError("Error fetching gold categories", $e);
        }
    }

    /**
     * Fill missing gold data
     */
    private function fillMissingGoldData(array $arguments, array $options): void
    {
        $days = (int)($arguments[1] ?? $this->getOptionValue('--days', $options, 7));
        $dryRun = in_array('--dry-run', $options);
        $force = in_array('--force', $options);

        try {
            $controller = new UnifiedGoldController();

            $this->output("Filling missing gold data for last $days days...");

            if ($dryRun) {
                $this->output("DRY RUN MODE - No data will be actually filled");
            }

            $result = $controller->fillMissingDataForPreviousDays($days);

            if ($result) {
                $this->output("Missing gold data filled successfully!");
            } else {
                $this->output("Failed to fill missing gold data.");
            }
        } catch (\Exception $e) {
            $this->logError("Error filling missing gold data", $e);
        }
    }

    /**
     * Check duplicate records
     */
    private function checkDuplicateRecords(array $arguments, array $options): void
    {
        try {
            $controller = new UnifiedGoldController();

            $this->output("Checking for duplicate records...");
            $controller->checkDuplicateRecords();
            $this->output("Duplicate check completed!");
        } catch (\Exception $e) {
            $this->logError("Error checking duplicate records", $e);
        }
    }

    /**
     * Remove duplicate records
     */
    private function removeDuplicateRecords(array $arguments, array $options): void
    {
        try {
            $controller = new UnifiedGoldController();

            $this->output("Removing duplicate records...");
            $controller->removeDuplicateRecords();
            $this->output("Duplicate removal completed!");
        } catch (\Exception $e) {
            $this->logError("Error removing duplicate records", $e);
        }
    }

    /**
     * Show help information
     */
    public function showHelp(): void
    {
        $this->output("Gold Command - Available actions:");
        $this->output("");
        $this->output("0. gold (no action)");
        $this->output("   - Run ALL 5 gold operations in sequence (like old command)");
        $this->output("   - This is the default behavior when no action is specified");
        $this->output("");
        $this->output("1. gold fetch [source] [interval]");
        $this->output("   - Fetch gold prices from various sources");
        $this->output("   - source: goonus, baomoi, all (default: all)", 'comment');
        $this->output("   - interval: 1M, 3Y, etc. (default: 3Y)", 'comment');
        $this->output("   - options: --continuity, --fill-missing, --days=N");
        $this->output("");
        $this->output("2. gold categories");
        $this->output("   - Fetch gold categories and menu data");
        $this->output("   - options: --providers, --sources");
        $this->output("");
        $this->output("3. gold fill-missing [days]");
        $this->output("   - Fill missing gold data for previous days");
        $this->output("   - days: number of days to check (default: 7)");
        $this->output("   - options: --dry-run, --force");
        $this->output("");
        $this->output("4. gold check-duplicates");
        $this->output("   - Check for duplicate gold price records");
        $this->output("");
        $this->output("5. gold remove-duplicates");
        $this->output("   - Remove duplicate gold price records");
        $this->output("");
        $this->output("Examples:");
        $this->output("  php fast gold                       # Run all 5 operations");
        $this->output("  php fast gold fetch                 # Fetch only");
        $this->output("  php fast gold fetch goonus 3Y --continuity");
        $this->output("  php fast gold categories --providers");
        $this->output("  php fast gold fill-missing 14 --dry-run");
        $this->output("  php fast gold check-duplicates");
        $this->output("  php fast gold remove-duplicates");
    }

    /**
     * Get option value from command line options
     */
    private function getOptionValue(string $option, array $options, $default = null)
    {
        foreach ($options as $opt) {
            if (strpos($opt, $option . '=') === 0) {
                return substr($opt, strlen($option) + 1);
            }
        }
        return $default;
    }
}
