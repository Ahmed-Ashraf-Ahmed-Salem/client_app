package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;
import lombok.Getter;
import lombok.Setter;

@Entity
@Table(name = "dm_clientapp")
@Getter
@Setter
public class DMClientApp {
    @Id
    @Column(name = "SERIAL")
    private Long serial;

    @Column(name = "NATIONALNO")
    private String nationalNo;
}

