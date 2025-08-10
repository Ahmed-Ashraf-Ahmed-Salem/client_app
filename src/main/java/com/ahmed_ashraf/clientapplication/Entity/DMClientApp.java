package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

import java.time.LocalDateTime;


@Entity
@Table(name = "dm_clientapp")
@Getter
@Setter
public class DMClientApp {

    @EmbeddedId
    private DMClientAppId id;

    @Column(name = "off_CODE")
    private String off_CODE;

    @Column(name = "APP_STAT")
    private String APP_STAT;

    @Column(name = "LASTUPDATE")
    private LocalDateTime LASTUPDATE;
}

