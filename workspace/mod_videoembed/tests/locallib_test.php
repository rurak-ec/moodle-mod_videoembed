<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for mod_videoembed URL parsing.
 *
 * @package    mod_videoembed
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videoembed;

/**
 * Tests for videoembed_parse_video_url() and the per-provider URL normalisation.
 *
 * @package    mod_videoembed
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Load the procedural library under test before the suite runs.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/videoembed/locallib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Data provider of valid URLs in canonical and alternate forms.
     *
     * @return array[] Each case is [input, expected provider, expected embed-url substring].
     */
    public static function valid_url_provider(): array {
        return [
            'youtube watch' => [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            'youtube short host' => [
                'https://youtu.be/dQw4w9WgXcQ',
                'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            'youtube embed' => [
                'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            'youtube shorts' => [
                'https://www.youtube.com/shorts/dQw4w9WgXcQ',
                'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            'youtube with start time' => [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1m30s',
                'youtube', 'start=90',
            ],
            'vimeo canonical' => [
                'https://vimeo.com/393011038',
                'vimeo', 'https://player.vimeo.com/video/393011038',
            ],
            'vimeo player host' => [
                'https://player.vimeo.com/video/393011038',
                'vimeo', 'https://player.vimeo.com/video/393011038',
            ],
            'bunny play' => [
                'https://player.mediadelivery.net/play/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c',
                'bunny', 'https://player.mediadelivery.net/embed/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c',
            ],
            'bunny embed alt host' => [
                'https://iframe.mediadelivery.net/embed/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c',
                'bunny', 'https://player.mediadelivery.net/embed/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c',
            ],
            'drive file' => [
                'https://drive.google.com/file/d/1A2B3C4D5E6F/view',
                'drive', 'https://drive.google.com/file/d/1A2B3C4D5E6F/preview',
            ],
            'drive open id' => [
                'https://drive.google.com/open?id=1A2B3C4D5E6F',
                'drive', 'https://drive.google.com/file/d/1A2B3C4D5E6F/preview',
            ],
        ];
    }

    /**
     * Valid URLs parse to the expected provider and canonical embed URL.
     *
     * @dataProvider valid_url_provider
     * @covers ::videoembed_parse_video_url
     * @param string $input The raw video URL.
     * @param string $expectedprovider The provider key expected.
     * @param string $expectedsubstring A substring expected in the embed URL.
     * @return void
     */
    public function test_valid_urls(string $input, string $expectedprovider, string $expectedsubstring): void {
        $parsed = videoembed_parse_video_url($input);
        $this->assertIsArray($parsed);
        $this->assertTrue($parsed['valid']);
        $this->assertSame($expectedprovider, $parsed['provider']);
        $this->assertStringContainsString($expectedsubstring, $parsed['embedurl']);
        $this->assertSame($input, $parsed['rawurl']);
    }

    /**
     * Data provider of malicious, unsupported or malformed URLs.
     *
     * @return array[] Each case is [input].
     */
    public static function invalid_url_provider(): array {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'unsupported host' => ['https://evil.example.com/watch?v=dQw4w9WgXcQ'],
            'lookalike host' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'],
            'drive without id' => ['https://drive.google.com/file/d//view'],
            'vimeo non numeric' => ['https://vimeo.com/notanumber'],
            'bunny missing video' => ['https://player.mediadelivery.net/play/620387'],
            'embedded quote in id' => ['https://www.youtube.com/watch?v=abc"onload=alert(1)'],
            'path traversal' => ['https://www.youtube.com/embed/../../etc/passwd'],
            'scheme relative no host' => ['/file/d/1A2B3C4D5E6F/view'],
            'whitespace garbage' => ['   not a url   '],
        ];
    }

    /**
     * Malicious, unsupported or malformed URLs are rejected (valid === false).
     *
     * @dataProvider invalid_url_provider
     * @covers ::videoembed_parse_video_url
     * @param string $input The raw video URL.
     * @return void
     */
    public function test_invalid_urls(string $input): void {
        $parsed = videoembed_parse_video_url($input);
        $this->assertIsArray($parsed);
        $this->assertFalse($parsed['valid']);
    }

    /**
     * Empty or whitespace-only input returns null (no URL configured).
     *
     * @covers ::videoembed_parse_video_url
     * @return void
     */
    public function test_empty_input_returns_null(): void {
        $this->assertNull(videoembed_parse_video_url(''));
        $this->assertNull(videoembed_parse_video_url('   '));
        $this->assertNull(videoembed_parse_video_url(null));
    }
}
