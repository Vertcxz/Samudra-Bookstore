<?php

namespace Tests\Feature\Petugas;

//use App\Http\Livewire\Petugas\Buku as BukuLivewire;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Penerbit;
use App\Models\Rak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BukuCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $kategori;
    protected $rak;
    protected $penerbit;

    protected function setUp(): void
    {
        parent::setUp();

        // Login user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Data master
        [$this->kategori, $this->rak, $this->penerbit] = $this->buatMasterData();
    }

    protected function buatMasterData()
    {
        $kategori = Kategori::create([
            'nama' => 'Novel',
            'slug' => 'novel',
        ]);

        $rak = Rak::create([
            'rak'         => 'A',
            'baris'       => '1',
            'kategori_id' => $kategori->id,
            'slug'        => 'a-1',
        ]);

        $penerbit = Penerbit::create([
            'nama' => 'Gramedia',
            'slug' => 'gramedia',
        ]);

        return [$kategori, $rak, $penerbit];
    }

    /** CREATE: tambah buku sukses */
    public function test_tambah_buku_dengan_data_valid()
    {
        Storage::fake('public');

        $sampulFake = UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg');

        Livewire::test(BukuLivewire::class)
            ->set('judul', 'laskar pelangi')
            ->set('penulis', 'andrea hirata')
            ->set('stok', 10)
            ->set('kategori_id', $this->kategori->id)
            ->set('rak_id', $this->rak->id)
            ->set('penerbit_id', $this->penerbit->id)
            ->set('sampul', $sampulFake)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('buku', [
            'judul'       => 'Laskar pelangi',
            'penulis'     => 'Andrea hirata',
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        $buku = Buku::first();
        Storage::disk('public')->assertExists($buku->sampul);
    }

    /** CREATE: validasi gagal */
    public function test_tambah_buku_gagal_karena_judul_kosong()
    {
        Storage::fake('public');

        $sampulFake = UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg');

        Livewire::test(BukuLivewire::class)
            ->set('judul', '')
            ->set('penulis', 'andrea hirata')
            ->set('stok', 10)
            ->set('kategori_id', $this->kategori->id)
            ->set('rak_id', $this->rak->id)
            ->set('penerbit_id', $this->penerbit->id)
            ->set('sampul', $sampulFake)
            ->call('store')
            ->assertHasErrors(['judul' => 'required']);

        $this->assertDatabaseCount('buku', 0);
    }

    /** READ: lihat daftar buku */
    public function test_menampilkan_daftar_buku()
    {
        Buku::create([
            'judul'       => 'Buku satu',
            'slug'        => 'buku-satu',
            'sampul'      => 'buku/cover1.jpg',
            'penulis'     => 'Penulis satu',
            'stok'        => 5,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        Buku::create([
            'judul'       => 'Buku dua',
            'slug'        => 'buku-dua',
            'sampul'      => 'buku/cover2.jpg',
            'penulis'     => 'Penulis dua',
            'stok'        => 3,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        Livewire::test(BukuLivewire::class)
            ->assertSee('Buku satu')
            ->assertSee('Buku dua');
    }

    /** READ: search buku */
    public function test_cari_buku_berdasarkan_judul()
    {
        Buku::create([
            'judul'       => 'Laskar Pelangi',
            'slug'        => 'laskar-pelangi',
            'sampul'      => 'buku/cover1.jpg',
            'penulis'     => 'Andrea',
            'stok'        => 5,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        Buku::create([
            'judul'       => 'Negeri 5 Menara',
            'slug'        => 'negeri-5-menara',
            'sampul'      => 'buku/cover2.jpg',
            'penulis'     => 'Ahmad',
            'stok'        => 4,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        Livewire::test(BukuLivewire::class)
            ->set('search', 'laskar')
            ->call('render')
            ->assertSee('Laskar Pelangi')
            ->assertDontSee('Negeri 5 Menara');
    }

    /** READ: show detail */
    public function test_lihat_detail_buku_mengisi_property_komponen()
    {
        $buku = Buku::create([
            'judul'       => 'Buku detail',
            'slug'        => 'buku-detail',
            'sampul'      => 'buku/cover-detail.jpg',
            'penulis'     => 'Penulis detail',
            'stok'        => 7,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        Livewire::test(BukuLivewire::class)
            ->call('show', $buku)
            ->assertSet('show', true)
            ->assertSet('judul', $buku->judul)
            ->assertSet('penulis', $buku->penulis)
            ->assertSet('stok', $buku->stok);
    }

    /** UPDATE: tanpa ganti sampul */
    public function test_update_buku_tanpa_mengganti_sampul()
    {
        Storage::fake('public');

        $oldPath = 'buku/old.jpg';
        Storage::disk('public')->put($oldPath, 'dummy');

        $buku = Buku::create([
            'judul'       => 'Buku lama',
            'slug'        => 'buku-lama',
            'sampul'      => $oldPath,
            'penulis'     => 'Penulis lama',
            'stok'        => 5,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        Livewire::test(BukuLivewire::class)
            ->set('buku_id', $buku->id)
            ->set('judul', 'buku diupdate')
            ->set('penulis', 'penulis diupdate')
            ->set('stok', 9)
            ->set('kategori_id', $this->kategori->id)
            ->set('rak_id', $this->rak->id)
            ->set('penerbit_id', $this->penerbit->id)
            ->call('update', $buku)
            ->assertHasNoErrors();

        $buku->refresh();

        $this->assertEquals('Buku diupdate', $buku->judul);
        $this->assertEquals('Penulis diupdate', $buku->penulis);
        $this->assertEquals(9, $buku->stok);
        $this->assertEquals($oldPath, $buku->sampul);

        Storage::disk('public')->assertExists($oldPath);
    }

    /** UPDATE: dengan ganti sampul */
    public function test_update_buku_dengan_mengganti_sampul()
    {
        Storage::fake('public');

        $oldPath = 'buku/old.jpg';
        Storage::disk('public')->put($oldPath, 'dummy');

        $buku = Buku::create([
            'judul'       => 'Buku lama',
            'slug'        => 'buku-lama',
            'sampul'      => $oldPath,
            'penulis'     => 'Penulis lama',
            'stok'        => 5,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        $newCover = UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg');

        Livewire::test(BukuLivewire::class)
            ->set('buku_id', $buku->id)
            ->set('judul', 'buku baru')
            ->set('penulis', 'penulis baru')
            ->set('stok', 6)
            ->set('kategori_id', $this->kategori->id)
            ->set('rak_id', $this->rak->id)
            ->set('penerbit_id', $this->penerbit->id)
            ->set('sampul', $newCover)
            ->call('update', $buku)
            ->assertHasNoErrors();

        $buku->refresh();

        $this->assertEquals('Buku baru', $buku->judul);
        $this->assertEquals('Penulis baru', $buku->penulis);
        $this->assertEquals(6, $buku->stok);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($buku->sampul);
    }

    /** DELETE: hapus buku */
    public function test_hapus_buku_menghapus_record_dan_file_sampul()
    {
        Storage::fake('public');

        $path = 'buku/cover.jpg';
        Storage::disk('public')->put($path, 'dummy');

        $buku = Buku::create([
            'judul'       => 'Buku hapus',
            'slug'        => 'buku-hapus',
            'sampul'      => $path,
            'penulis'     => 'Penulis hapus',
            'stok'        => 2,
            'kategori_id' => $this->kategori->id,
            'rak_id'      => $this->rak->id,
            'penerbit_id' => $this->penerbit->id,
        ]);

        Livewire::test(BukuLivewire::class)
            ->call('destroy', $buku);

        $this->assertDatabaseMissing('buku', ['id' => $buku->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
