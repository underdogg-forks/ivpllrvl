<?php

namespace Modules\Core\Testing\Fakes;

/**
 * Fake Session
 * 
 * Provides a fake session implementation for testing.
 * Stores session data in memory instead of using actual PHP sessions.
 */
class FakeSession
{
    protected array $data = [];
    protected array $flashData = [];
    protected array $tempData = [];

    /**
     * Set session data
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get session data
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if session key exists
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove session data
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Set multiple session values
     */
    public function setMultiple(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $this->data = [];
        $this->flashData = [];
        $this->tempData = [];
    }

    /**
     * Set flash data (available for next request only)
     */
    public function setFlash(string $key, mixed $value): void
    {
        $this->flashData[$key] = $value;
    }

    /**
     * Get flash data
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->flashData[$key] ?? $default;
        unset($this->flashData[$key]);
        return $value;
    }

    /**
     * Check if flash data exists
     */
    public function hasFlash(string $key): bool
    {
        return isset($this->flashData[$key]);
    }

    /**
     * Set temporary data (expires after specified time)
     */
    public function setTemp(string $key, mixed $value, int $ttl = 300): void
    {
        $this->tempData[$key] = [
            'value' => $value,
            'expires' => time() + $ttl,
        ];
    }

    /**
     * Get temporary data
     */
    public function getTemp(string $key, mixed $default = null): mixed
    {
        if (!isset($this->tempData[$key])) {
            return $default;
        }

        if ($this->tempData[$key]['expires'] < time()) {
            unset($this->tempData[$key]);
            return $default;
        }

        return $this->tempData[$key]['value'];
    }

    /**
     * Destroy session
     */
    public function destroy(): void
    {
        $this->clear();
    }

    /**
     * Regenerate session ID
     */
    public function regenerate(bool $deleteOldSession = false): bool
    {
        // In testing, this is a no-op
        return true;
    }
}
