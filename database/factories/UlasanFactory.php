<?php

namespace Database\Factories;

use App\Models\Resep;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ulasan>
 */
class UlasanFactory extends Factory
{
    protected $model = Ulasan::class;

    /**
     * Ulasan contoh dengan rating dan komentar yang masuk akal.
     *
     * Komentar dibuat dari potongan kalimat yang benar-benar dipakai orang
     * saat menilai masakan, bukan lorem ipsum. Ulasan palsu yang berbunyi
     * "Lorem ipsum dolor sit amet" membuat halaman detail terlihat rusak
     * saat diperiksa manual, dan menyulitkan menilai apakah tata letaknya
     * sudah benar.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'resep_id' => Resep::factory(),
            'rating' => fake()->numberBetween(Ulasan::RATING_MIN, Ulasan::RATING_MAKS),
            'komentar' => fake()->randomElement([
                'Bumbunya meresap sampai ke dalam, semua di rumah suka.',
                'Pertama kali masak ini dan hasilnya langsung enak.',
                'Saya kurangi cabainya untuk anak-anak, tetap gurih.',
                'Langkahnya jelas, tidak ada yang bikin bingung.',
                'Perlu waktu lebih lama dari yang tertulis, tapi sepadan.',
                'Santan saya sedikit pecah, tapi rasanya tetap enak.',
            ]),
        ];
    }

    /**
     * Ulasan tanpa komentar — hanya bintang.
     *
     * Bentuk ini sah dan memang sering dipakai: orang yang puas tapi tidak
     * ingin menulis apa-apa. Halaman detail harus tetap rapi menghadapinya.
     */
    public function tanpaKomentar(): static
    {
        return $this->state(fn () => ['komentar' => null]);
    }

    /**
     * Ulasan yang sudah dibalas pemilik resep.
     */
    public function dibalas(): static
    {
        return $this->state(fn () => [
            'balasan' => 'Terima kasih sudah mencoba, senang mendengarnya!',
            'balasan_at' => now(),
        ]);
    }

    /**
     * Rating tertentu — dipakai saat penguji butuh rata-rata yang pasti.
     */
    public function rating(int $bintang): static
    {
        return $this->state(fn () => ['rating' => $bintang]);
    }
}
