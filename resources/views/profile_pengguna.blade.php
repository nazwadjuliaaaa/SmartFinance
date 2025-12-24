@extends('layouts.app')

@section('title', 'Profil Pengguna - SmartFinance')

@section('content')
    <h2 class="page-title">Profil Pengguna</h2>
    
    @if(session('success'))
        <div style="background: rgba(212, 175, 55, 0.1); color: #b8860b; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center; border: 1px solid rgba(212, 175, 55, 0.3);">
            ✨ {{ session('success') }}
        </div>
    @endif

    <div class="input-section" style="max-width: 800px; padding: 0; overflow: hidden; position: relative;">
        <!-- decorative header -->
        <div style="height: 150px; background: linear-gradient(135deg, var(--primary-color) 0%, #1e293b 100%); position: relative;">
            <div style="position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%);">
                <div style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: white; overflow: hidden; position: relative;">
                     @if(Auth::user()->profile_photo)
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 100%; background: #1e293b; color: #d4af37; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold;">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div style="padding: 60px 40px 40px;">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <label for="photo_input" style="color: var(--accent-color); font-weight: 600; cursor: pointer; display: inline-block; padding: 5px 15px; border: 1px solid var(--accent-color); border-radius: 20px; font-size: 0.9rem;">
                        <span style="font-size: 1.1rem; margin-right: 5px;">📷</span> Ganti Foto
                    </label>
                    <input type="file" name="photo" id="photo_input" style="display: none;" accept="image/*" onchange="document.getElementById('file-chosen').textContent = this.files[0].name">
                    <div id="file-chosen" style="margin-top: 5px; font-size: 0.8rem; color: #64748b;"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="color: #64748b; font-size: 0.9rem;">Nama Lengkap</label>
                        <input type="text" name="name" class="input-field" value="{{ old('name', Auth::user()->name) }}" style="width: 100%; box-sizing: border-box;" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="color: #64748b; font-size: 0.9rem;">Username</label>
                        <input type="text" name="username" class="input-field" value="{{ old('username', Auth::user()->username) }}" style="width: 100%; box-sizing: border-box;" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                        <label style="color: #64748b; font-size: 0.9rem;">Alamat Email</label>
                        <input type="email" name="email" class="input-field" value="{{ old('email', Auth::user()->email) }}" style="width: 100%; box-sizing: border-box;" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                        <label style="color: #64748b; font-size: 0.9rem;">Password Baru <span style="font-weight: normal; color: #94a3b8;">(Opsional)</span></label>
                        <input type="password" name="password" class="input-field" placeholder="Kosongkan jika tidak ingin mengubah password" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="text-align: center; border-top: 1px solid #f1f5f9; margin-top: 30px; padding-top: 30px;">
                    <button type="submit" class="btn-submit" style="margin: 0 auto; width: 100%; max-width: 300px;">
                        Simpan Perubahan
                    </button>
                    <p style="margin-top: 15px; font-size: 0.85rem; color: #94a3b8;">
                        Data Anda aman bersama SmartFinance.
                    </p>
                </div>
            </form>
        </div>
    </div>
@endsection
