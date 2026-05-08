<?php

namespace App\Console\Commands;

use App\Models\AdminPortal;
use App\Models\NotificationEmail;
use App\Models\Service;
use App\Models\ServiceCheck;
use App\Models\SmtpSetting;
use App\Notifications\ServicesDownNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class CheckServices extends Command
{
    protected $signature = 'services:check';

    protected $description = 'Check all active services and admin portals';

    public function handle(): void
    {
        $this->checkServices();
        $this->checkAdminPortals();
    }

    private function checkServices(): void
    {
        $services = Service::where('is_active', true)->get();

        foreach ($services as $service) {
            $result = $this->performServiceCheck($service);

            ServiceCheck::create([
                'service_id'      => $service->id,
                'status'          => $result['status'],
                'response_time'   => $result['response_time'] ?? null,
                'protocol_detail' => $result['protocol_detail'] ?? null,
                'error_message'   => $result['error_message'] ?? null,
                'checked_at'      => now(),
            ]);

            $addr = $service->port !== null ? "{$service->host}:{$service->port}" : $service->host;
            $suffix = ($result['status'] === 'down' && ! empty($result['error_message']))
                ? ' — ' . $result['error_message']
                : '';
            $this->line("[{$result['status']}] {$service->display_name} ({$addr}){$suffix}");
        }

        $this->maybeSendDownNotification();
    }

    private function maybeSendDownNotification(): void
    {
        $downServices = Service::where('is_active', true)
            ->whereHas('latestCheck', fn ($q) => $q->where('status', 'down'))
            ->get();

        if ($downServices->isEmpty()) {
            return;
        }

        $smtp = SmtpSetting::first();
        if (! $smtp) {
            return;
        }

        $interval = $smtp->notification_interval_minutes;

        if ($smtp->last_notified_at && $smtp->last_notified_at->addMinutes($interval)->isFuture()) {
            return;
        }

        $this->sendDownNotification($downServices, $smtp);
    }

    private function performServiceCheck(Service $service): array
    {
        $start = microtime(true);

        try {
            return match ($service->protocol) {
                'tcp'      => $this->checkTcp($service->host, $service->port, $start),
                'http'     => $this->checkHttp("http://{$service->host}:{$service->port}", $start),
                'https'    => $this->checkHttp("https://{$service->host}:{$service->port}", $start),
                'ssh'      => $this->checkSsh($service->host, $service->port, $start),
                'rdp'      => $this->checkRdp($service->host, $service->port, $start),
                'udp'      => $this->checkUdp($service->host, $service->port, $start),
                'database' => $this->checkDatabase($service->host, $service->port, $start),
                'ftp'      => $this->checkFtp($service->host, $service->port, $start),
                'ftps'     => $this->checkFtpSsl($service->host, $service->port, $start),
                'smtp'     => $this->checkSmtp($service->host, $service->port, $start),
                'smtps'    => $this->checkSmtpSsl($service->host, $service->port, $start),
                'icmp'     => $this->checkIcmp($service->host, $start),
                'irc'      => $this->checkIrc($service->host, $service->port, $start),
                'smb'      => $this->checkSmb($service->host, $service->port, $start),
                'ldap'     => $this->checkLdap($service->host, $service->port, $start),
                'ldaps'    => $this->checkLdapSsl($service->host, $service->port, $start),
            };
        } catch (\Throwable $e) {
            return [
                'status'        => 'down',
                'response_time' => round((microtime(true) - $start) * 1000, 2),
                'error_message' => $e->getMessage(),
            ];
        }
    }

    private function checkTcp(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if ($fp) {
            fclose($fp);

            return ['status' => 'up', 'response_time' => $responseTime];
        }

        return [
            'status'        => 'down',
            'response_time' => $responseTime,
            'error_message' => $errstr,
        ];
    }

    private function checkHttp(string $url, float $start): array
    {
        $response = Http::timeout(10)
            ->withOptions(['verify' => false, 'allow_redirects' => true, 'http_errors' => false])
            ->get($url);

        $responseTime = round((microtime(true) - $start) * 1000, 2);
        $code = $response->status();

        return [
            'status'          => ($code < 500) ? 'up' : 'down',
            'response_time'   => $responseTime,
            'protocol_detail' => (string) $code,
        ];
    }

    private function checkSsh(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        stream_set_timeout($fp, 3);
        $banner = fgets($fp, 256);
        fclose($fp);

        $detail = null;
        if ($banner && preg_match('/^(SSH-\S+)/', trim($banner), $m)) {
            $detail = $m[1];
        }

        return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => $detail];
    }

    private function checkRdp(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        // TPKT + COTP Connection Request + RDP Negotiation Request
        $pkt = "\x03\x00\x00\x13"               // TPKT header (version=3, length=19)
             . "\x0e\xe0\x00\x00\x00\x00\x00"   // COTP CR (e0 = Connection Request)
             . "\x01\x00\x08\x00\x03\x00\x00\x00"; // RDP_NEG_REQ (protocols: TLS+CredSSP)

        fwrite($fp, $pkt);
        stream_set_timeout($fp, 3);
        $resp = fread($fp, 64);
        fclose($fp);

        // Expect TPKT + COTP CC (Connection Confirm = 0xD0)
        if (strlen($resp) >= 6 && ord($resp[0]) === 0x03 && ord($resp[5]) === 0xD0) {
            $detail = null;
            // RDP_NEG_RSP starts at offset 11: type(1) flags(1) length(2) selectedProtocol(4)
            if (strlen($resp) >= 19 && ord($resp[11]) === 0x02) {
                $protocol = unpack('V', substr($resp, 15, 4))[1] ?? 0;
                $detail = match ($protocol) {
                    0 => 'RDP', 1 => 'TLS', 2 => 'NLA', 4 => 'RDSTLS', 8 => 'NLA+EX',
                    default => 'proto:' . $protocol,
                };
            }

            return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => $detail];
        }

        return ['status' => 'down', 'response_time' => $responseTime];
    }

    private function checkUdp(string $host, int $port, float $start): array
    {
        $fp = @fsockopen("udp://{$host}", $port, $errno, $errstr, 3);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        fwrite($fp, "\x00");
        stream_set_timeout($fp, 2);
        $data = @fread($fp, 256);
        $meta = stream_get_meta_data($fp);
        fclose($fp);

        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if ($data !== false && strlen($data) > 0) {
            return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => 'responded'];
        }

        // No response on UDP can mean filtered or simply a silent service; report DOWN
        return [
            'status'          => 'down',
            'response_time'   => $responseTime,
            'protocol_detail' => $meta['timed_out'] ? 'timeout' : null,
        ];
    }

    private function checkDatabase(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        stream_set_timeout($fp, 3);
        $greeting = @fread($fp, 64);
        fclose($fp);

        $detail = null;
        // MySQL/MariaDB: 4-byte packet header + 0x0a protocol byte + null-terminated version
        if ($greeting !== false && strlen($greeting) >= 6 && ord($greeting[4]) === 0x0a) {
            $end = strpos($greeting, "\x00", 5);
            if ($end !== false) {
                $detail = substr($greeting, 5, min($end - 5, 40));
            }
        }

        return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => $detail];
    }

    private function checkFtp(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        stream_set_timeout($fp, 3);
        $banner = fgets($fp, 512);
        fclose($fp);

        preg_match('/^(\d{3})/', (string) $banner, $m);
        $code = isset($m[1]) ? (int) $m[1] : null;

        return [
            'status'          => ($code && $code >= 200 && $code < 400) ? 'up' : 'down',
            'response_time'   => $responseTime,
            'protocol_detail' => $code !== null ? (string) $code : null,
        ];
    }

    private function checkSmtp(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        stream_set_timeout($fp, 3);
        $banner = fgets($fp, 512);
        fclose($fp);

        preg_match('/^(\d{3})/', (string) $banner, $m);
        $code = isset($m[1]) ? (int) $m[1] : null;

        return [
            'status'          => ($code && $code >= 200 && $code < 400) ? 'up' : 'down',
            'response_time'   => $responseTime,
            'protocol_detail' => $code !== null ? (string) $code : null,
        ];
    }

    private function checkIcmp(string $host, float $start): array
    {
        $output = [];
        $exitCode = 0;
        exec('ping -c 1 -W 2 ' . escapeshellarg($host) . ' 2>&1', $output, $exitCode);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        $detail = null;
        if ($exitCode === 0) {
            foreach ($output as $line) {
                if (preg_match('/ttl=(\d+)/i', $line, $m)) {
                    $detail = 'TTL=' . $m[1];
                    break;
                }
            }
        }

        return [
            'status'          => $exitCode === 0 ? 'up' : 'down',
            'response_time'   => $responseTime,
            'protocol_detail' => $detail,
        ];
    }

    private function checkIrc(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        stream_set_timeout($fp, 3);
        $line = fgets($fp, 512);
        fclose($fp);

        $detail = null;
        if ($line && preg_match('/^:(\S+)\s/', trim($line), $m)) {
            $detail = $m[1];
        }

        return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => $detail];
    }

    private function checkSmb(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        // SMBv1 Negotiate Request
        $payload = "\xff\x53\x4d\x42"              // SMB magic (\xffSMB)
            . "\x72"                                // Command: NEGOTIATE (0x72)
            . "\x00\x00\x00\x00"                  // NT status
            . "\x18"                                // Flags
            . "\x28\x01"                           // Flags2 (LE = 0x0128)
            . "\x00\x00"                           // Process ID high
            . str_repeat("\x00", 8)                // Security signature
            . "\x00\x00"                           // Reserved
            . "\xff\xff"                           // TID
            . "\xff\xfe"                           // PID
            . "\xff\xff"                           // UID
            . "\x00\x00"                           // MID
            . "\x00"                                // Word count
            . "\x0c\x00"                           // Byte count = 12
            . "\x02NT LM 0.12\x00";               // Dialect

        fwrite($fp, "\x00\x00" . pack('n', strlen($payload)) . $payload);
        stream_set_timeout($fp, 3);
        $resp = fread($fp, 256);
        fclose($fp);

        if (strlen($resp) < 8) {
            return ['status' => 'down', 'response_time' => $responseTime];
        }

        $magic = substr($resp, 4, 4);
        $detail = match (true) {
            $magic === "\xfeSMB" => 'SMB2',
            $magic === "\xffSMB" => 'SMB1',
            default              => null,
        };

        return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => $detail];
    }

    private function checkLdap(string $host, int $port, float $start): array
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $errstr];
        }

        // Anonymous LDAP BindRequest (BER-encoded LDAPMessage)
        // SEQUENCE { INTEGER 1, [APPLICATION 0] { INTEGER 3, OCTET STRING "", [0] "" } }
        $bindRequest = "\x30\x0c"       // SEQUENCE, 12 bytes
            . "\x02\x01\x01"           // INTEGER 1 (messageID)
            . "\x60\x07"               // [APPLICATION 0] BindRequest, 7 bytes
            . "\x02\x01\x03"           // INTEGER 3 (LDAP version)
            . "\x04\x00"               // OCTET STRING "" (name)
            . "\x80\x00";              // [CONTEXT 0] simple: "" (password)

        fwrite($fp, $bindRequest);
        stream_set_timeout($fp, 3);
        $resp = fread($fp, 256);
        fclose($fp);

        // BindResponse: SEQUENCE { INTEGER msgID, [APPLICATION 1] { ENUMERATED resultCode, ... } }
        if (strlen($resp) >= 10 && ord($resp[0]) === 0x30 && ord($resp[5]) === 0x61) {
            $resultCode = ord($resp[9]);
            $detail = match ($resultCode) {
                0  => 'success',
                7  => 'authMethodNotSupported',
                13 => 'confidentialityRequired',
                48 => 'anonDisabled',
                49 => 'invalidCredentials',
                default => 'rc:' . $resultCode,
            };

            return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => $detail];
        }

        return ['status' => 'down', 'response_time' => $responseTime];
    }

    /**
     * @return array{0: resource|false, 1: string}  [socket, errorMessage]
     */
    private function openSslSocket(string $host, int $port): array
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
                'peer_name'         => $host, // SNI — indispensable sur les hôtes mutualisés
            ],
        ]);

        // PHP 8.1+ interdit de passer null à un paramètre int& défini par l'utilisateur.
        // On initialise nous-mêmes errno/errstr et on capture les warnings OpenSSL.
        $errno    = 0;
        $errstr   = '';
        $phpError = '';

        set_error_handler(static function (int $_, string $msg) use (&$phpError): bool {
            $phpError = $msg;
            return true;
        });

        $fp = stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        restore_error_handler();

        $error = $errstr !== '' ? $errstr : $phpError;

        return [$fp, $error];
    }

    private function checkFtpSsl(string $host, int $port, float $start): array
    {
        [$fp, $sslError] = $this->openSslSocket($host, $port);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $sslError];
        }

        stream_set_timeout($fp, 3);
        $banner = fgets($fp, 512);
        fclose($fp);

        preg_match('/^(\d{3})/', (string) $banner, $m);
        $code = isset($m[1]) ? (int) $m[1] : null;

        return [
            'status'          => ($code && $code >= 200 && $code < 400) ? 'up' : 'down',
            'response_time'   => $responseTime,
            'protocol_detail' => $code !== null ? (string) $code : null,
        ];
    }

    private function checkSmtpSsl(string $host, int $port, float $start): array
    {
        [$fp, $sslError] = $this->openSslSocket($host, $port);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $sslError];
        }

        stream_set_timeout($fp, 3);
        $banner = fgets($fp, 512);
        fclose($fp);

        preg_match('/^(\d{3})/', (string) $banner, $m);
        $code = isset($m[1]) ? (int) $m[1] : null;

        return [
            'status'          => ($code && $code >= 200 && $code < 400) ? 'up' : 'down',
            'response_time'   => $responseTime,
            'protocol_detail' => $code !== null ? (string) $code : null,
        ];
    }

    private function checkLdapSsl(string $host, int $port, float $start): array
    {
        [$fp, $sslError] = $this->openSslSocket($host, $port);
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        if (! $fp) {
            return ['status' => 'down', 'response_time' => $responseTime, 'error_message' => $sslError];
        }

        // Anonymous LDAP bind — identique au LDAP plain
        $bindRequest = "\x30\x0c\x02\x01\x01\x60\x07\x02\x01\x03\x04\x00\x80\x00";

        fwrite($fp, $bindRequest);
        stream_set_timeout($fp, 3);
        $resp = fread($fp, 256);
        fclose($fp);

        if (strlen($resp) >= 10 && ord($resp[0]) === 0x30 && ord($resp[5]) === 0x61) {
            $resultCode = ord($resp[9]);
            $detail = match ($resultCode) {
                0  => 'success',
                7  => 'authMethodNotSupported',
                13 => 'confidentialityRequired',
                48 => 'anonDisabled',
                49 => 'invalidCredentials',
                default => 'rc:' . $resultCode,
            };

            return ['status' => 'up', 'response_time' => $responseTime, 'protocol_detail' => $detail];
        }

        return ['status' => 'down', 'response_time' => $responseTime];
    }

    private function checkAdminPortals(): void
    {
        $portals = AdminPortal::where('is_active', true)->get();

        foreach ($portals as $portal) {
            try {
                $response = Http::timeout(10)
                    ->withOptions(['verify' => false, 'allow_redirects' => true, 'http_errors' => false])
                    ->get($portal->url);

                $isUp = $response->status() < 500;

                $portal->update([
                    'last_http_status' => $response->status(),
                    'last_status'      => $isUp,
                    'last_checked_at'  => now(),
                ]);
            } catch (\Throwable) {
                $portal->update([
                    'last_http_status' => null,
                    'last_status'      => false,
                    'last_checked_at'  => now(),
                ]);
            }
        }
    }

    private function sendDownNotification(\Illuminate\Support\Collection $services, SmtpSetting $smtp): void
    {
        $emails = NotificationEmail::where('is_active', true)->get();
        if ($emails->isEmpty()) {
            return;
        }

        Config::set('mail.mailers.smtp.host', $smtp->host);
        Config::set('mail.mailers.smtp.port', $smtp->port);
        Config::set('mail.mailers.smtp.username', $smtp->username);
        Config::set('mail.mailers.smtp.password', decrypt($smtp->password));
        Config::set('mail.mailers.smtp.encryption', $smtp->encryption === 'none' ? null : $smtp->encryption);
        Config::set('mail.default', 'smtp');
        Config::set('mail.from.address', $smtp->from_address);
        Config::set('mail.from.name', $smtp->from_name);

        foreach ($emails as $email) {
            Notification::route('mail', $email->email)
                ->notify(new ServicesDownNotification($services));
        }

        $smtp->update(['last_notified_at' => now()]);

        $this->info("Notification sent for {$services->count()} down service(s).");
    }
}
