<?php

namespace DeptOfScrapyardRobotics\Sensors\VL6180X;

use GPIO\Contracts\I2C\I2CCompatibleTransport;
use BareMetal\Contracts\Circuits\BootScaffolding;
use ScrapyardIO\NutsAndBolts\Concerns\Splices16Bits;
use DeptOfScrapyardRobotics\Sensors\VL6180X\Enums\VL6180OpCode;
use GPIO\Contracts\Digital\DigitalInputPin as DigitalInputInterface;
use GPIO\Contracts\Digital\DigitalOutputPin as DigitalOutputInterface;

/**
 * @property-read DigitalInputInterface $interrupt_pin
 * @property-read DigitalOutputInterface $shutdown_pin
 * @property-read I2CCompatibleTransport $i2c
 */
trait VL6180XInternalAPI
{
    use BootScaffolding, Splices16Bits;

    protected int $hardwired_device_id = 0xB4;

    protected function sendCommand(VL6180OpCode $register, array $command_data = []): int
    {
        $payload = [...array_values($this->splitBytes($register->value)), ...$command_data];
        return $this->write($payload);
    }

    /**
     * @throws VL6180XException
     */
    protected function readData(VL6180OpCode $register, int $length, int $attempts = 5): array
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $data = $this->i2c->writeRead([...array_values($this->splitBytes($register->value))], $length);

            if ($data !== false) {
                return $data;
            }

            usleep(500);
        }

        throw VL6180XException::i2cReadFailed($register->value, $length);
    }



    /**
     * @throws VL6180XException
     */
    protected function _boot(): void
    {
        $this->resetDevice();
        $this->confirmDeviceId();
        $this->loadSettings();
        $this->loadRecommendedSettings();
    }

    protected function resetDevice(): void
    {
        if(!empty($this->shutdown_pin))
        {
            $this->shutdown_pin->low();
            usleep(2000);
            $this->shutdown_pin->high();
            usleep(2000);
        }
    }

    protected function loadSettings(): void
    {
        if ($this->system_fresh_out_of_reset) {
            $this->loadProprietarySettings();
            $this->system_fresh_out_of_reset = false;
        }
    }

    protected function loadProprietarySettings(): void
    {
        $settings = [
            [0x0207, 0x01], [0x0208, 0x01], [0x0096, 0x00], [0x0097, 0xFD],
            [0x00E3, 0x00], [0x00E4, 0x04], [0x00E5, 0x02], [0x00E6, 0x01],
            [0x00E7, 0x03], [0x00F5, 0x02], [0x00D9, 0x05], [0x00DB, 0xCE],
            [0x00DC, 0x03], [0x00DD, 0xF8], [0x009F, 0x00], [0x00A3, 0x3C],
            [0x00B7, 0x00], [0x00BB, 0x3C], [0x00B2, 0x09], [0x00CA, 0x09],
            [0x0198, 0x01], [0x01B0, 0x17], [0x01AD, 0x00], [0x00FF, 0x05],
            [0x0100, 0x05], [0x0199, 0x05], [0x01A6, 0x1B], [0x01AC, 0x3E],
            [0x01A7, 0x1F], [0x0030, 0x00],
        ];

        foreach ($settings as $cmd) {
            $payload = [...array_values($this->splitBytes($cmd[0])), $cmd[1]];
            $this->write($payload);
        }
    }

    /**
     * @return void
     */
    protected function loadRecommendedSettings(): void
    {
        $this->int_pin_mode = $this->_int_pin_mode;
        $this->avg_sample_period_readout = $this->_avg_sample_period_readout;
        $this->analog_gain = $this->_analog_gain;
        $this->vhv_repeat_rate = $this->_vhv_repeat_rate;
        $this->sys_als_integration_period = $this->_sys_als_integration_period;
        $this->vhv_recalibrate = $this->_vhv_recalibrate;
        $this->sys_range_integration_period = $this->_sys_range_integration_period;
        $this->intermeasurement_period = $this->_intermeasurement_period;
        $this->interrupt_pin_config = $this->_interrupt_pin_config;
    }

    /**
     * @throws VL6180XException
     */
    protected function confirmDeviceId(): void
    {
        $device_id = $this->device_id;
        if ($device_id !== $this->hardwired_device_id) {
            throw VL6180XException::invalidModelId($device_id);
        }
    }

    private function write(array $data = []): int
    {
        $bytes = array2bytes($data);
        return $this->i2c->write($bytes);
    }
}
